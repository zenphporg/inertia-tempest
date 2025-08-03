<?php

declare(strict_types=1);

namespace Inertia;

use Closure;
use Deprecated;
use Inertia\Configs\InertiaConfig;
use Inertia\Exceptions\ComponentNotFoundException;
use Inertia\Props\AlwaysProp;
use Inertia\Props\DeferProp;
use Inertia\Props\LazyProp;
use Inertia\Props\MergeProp;
use Inertia\Props\OptionalProp;
use Inertia\Support\Header;
use Tempest\Container\Singleton;
use Tempest\Http\GenericResponse;
use Tempest\Http\Request;
use Tempest\Http\Responses\Redirect;
use Tempest\Http\Session\Session;
use Tempest\Http\Status;
use Tempest\Support\Arr;
use Tempest\Support\Arr\ArrayInterface;

use function Tempest\get;
use function Tempest\invoke;

#[Singleton]
final class ResponseFactory
{
    private string $rootView = 'inertia.view.php';

    private array $sharedProps = [];

    private Closure|string|int|float|null $version = null;

    private bool $clearHistory = false;

    private ?bool $encryptHistory = null;

    private ?Closure $urlResolver = null;

    private Session $session {
        get => get(Session::class);
    }

    private Request $request {
        get => get(Request::class);
    }

    private InertiaConfig $config {
        get => get(InertiaConfig::class);
    }

    /**
     * Sets the root view that all Inertia responses will use.
     */
    public function setRootView(string $name): void
    {
        $this->rootView = $name;
    }

    /**
     * Add shared data to the response.
     */
    public function share(string|array|ArrayInterface $key, mixed $value = null): void
    {
        if ($key instanceof ArrayInterface) {
            $this->sharedProps = Arr\merge($this->sharedProps, $key->toArray());
        } elseif (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } else {
            $this->sharedProps = Arr\set_by_key($this->sharedProps, $key, $value);
        }
    }

    /**
     * Get the shared data.
     */
    public function getShared(?string $key = null, mixed $default = null): mixed
    {
        if ($key) {
            return Arr\get_by_key($this->sharedProps, $key, $default);
        }

        return $this->sharedProps;
    }

    /**
     * Flush the shared data.
     */
    public function flushShared(): void
    {
        $this->sharedProps = [];
    }

    /**
     * Sets the asset version.
     */
    public function version(Closure|string|int|float|null $version): void
    {
        $this->version = $version;
    }

    /**
     * Gets the asset version.
     */
    public function getVersion(): string
    {
        $version = ($this->version instanceof Closure) ? invoke($this->version) : $this->version;

        return (string) $version;
    }

    /**
     * Sets the URL resolver.
     */
    public function resolveUrlUsing(?Closure $urlResolver = null): void
    {
        $this->urlResolver = $urlResolver;
    }

    /**
     * Clears the history.
     */
    public function clearHistory(): void
    {
        $this->session->set('inertia.clear_history', true);
    }

    /**
     * Encrypts the history.
     */
    public function encryptHistory(bool $encrypt = true): void
    {
        $this->encryptHistory = $encrypt;
    }

    #[Deprecated(message: 'Use `optional` instead.')]
    public function lazy(callable $callback): LazyProp
    {
        return new LazyProp($callback);
    }

    /**
     * Create a new optional property.
     */
    public function optional(callable $callback): OptionalProp
    {
        return new OptionalProp($callback);
    }

    /**
     * Create a new deferred property.
     */
    public function defer(callable $callback, string $group = 'default'): DeferProp
    {
        return new DeferProp($callback, $group);
    }

    /**
     * Create a new mergeable property.
     */
    public function merge(mixed $value): MergeProp
    {
        return new MergeProp($value);
    }

    /**
     * Create a new deep mergeable property.
     */
    public function deepMerge(mixed $value): MergeProp
    {
        return new MergeProp($value)->deepMerge();
    }

    /**
     * Create a new always property.
     */
    public function always(mixed $value): AlwaysProp
    {
        return new AlwaysProp($value);
    }

    /**
     * Create a new Inertia response.
     */
    public function render(string $component, array|ArrayInterface $props = []): Response
    {
        if ($this->config->pages->ensure_pages_exists) {
            $this->findComponentOrFail($component);
        }

        if ($props instanceof ArrayInterface) {
            $props = $props->toArray();
        }

        return new Response(
            $component,
            array_merge($this->sharedProps, $props),
            $this->rootView,
            $this->getVersion(),
            $this->clearHistory,
            $this->encryptHistory ?? $this->config->history->encrypt,
            $this->urlResolver,
        );
    }

    /**
     * Redirect to a new location.
     */
    public function location(string|Redirect $url): GenericResponse|Redirect
    {
        if ($this->request->headers->has(Header::INERTIA)) {
            if ($url instanceof Redirect) {
                $url = $url->getHeader('Location')->values[0];
            }

            return new GenericResponse(
                status: Status::CONFLICT,
                headers: [Header::LOCATION => $url],
            );
        }

        return ($url instanceof Redirect) ? $url : new Redirect($url);
    }

    private function findComponentOrFail(string $component): void
    {
        $componentPath = str_replace('/', DIRECTORY_SEPARATOR, $component);

        $paths = $this->config->pages->page_paths;
        $extensions = $this->config->pages->page_extensions;

        foreach ($paths as $path) {
            foreach ($extensions as $extension) {
                $ext = str_starts_with((string) $extension, '.') ? $extension : ('.' . $extension);

                if (file_exists($path . DIRECTORY_SEPARATOR . $componentPath . $ext)) {
                    return;
                }
            }
        }

        throw new ComponentNotFoundException($component, $paths);
    }
}
