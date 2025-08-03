<?php

declare(strict_types=1);

namespace Inertia;

use Closure;
use DateInterval;
use DateTimeImmutable;
use GuzzleHttp\Promise\PromiseInterface;
use Inertia\Configs\InertiaConfig;
use Inertia\Contracts\Arrayable;
use Inertia\Contracts\IgnoreFirstLoad;
use Inertia\Contracts\Mergeable;
use Inertia\Props\AlwaysProp;
use Inertia\Props\DeferProp;
use Inertia\Props\LazyProp;
use Inertia\Props\MergeProp;
use Inertia\Props\OptionalProp;
use Inertia\Ssr\Contracts\Gateway;
use Inertia\Ssr\Response as SsrResponse;
use Inertia\Support\Header;
use Inertia\Support\PaginatorAdapter;
use Inertia\Views\InertiaView;
use JsonSerializable;
use Tempest\Http\ContentType;
use Tempest\Http\IsResponse;
use Tempest\Http\Method;
use Tempest\Http\Request;
use Tempest\Http\Response as HttpResponse;
use Tempest\Http\Status;
use Tempest\Support\Arr;
use Tempest\Support\Arr\ArrayInterface;
use Tempest\Support\Paginator\PaginatedData;
use Tempest\Support\Str;

use function Tempest\get;
use function Tempest\invoke;

final class Response implements HttpResponse
{
    use IsResponse;

    private array $viewData = [];

    private array $cacheFor = [];

    private Request $request {
        get => get(Request::class);
    }

    private InertiaConfig $config {
        get => get(InertiaConfig::class);
    }

    private Gateway $gateway {
        get => get(Gateway::class);
    }

    public function __construct(
        private string $component,
        private array|ArrayInterface $props,
        private string $rootView = 'inertia.view.php',
        private string $version = '',
        private bool $clearHistory = false,
        private bool $encryptHistory = false,
        private ?Closure $urlResolver = null,
    ) {
        $resolvedProps = $this->resolveProperties($this->normalizeProps($this->props));

        $page = array_merge(
            [
                'component' => $this->component,
                'props' => $resolvedProps,
                'url' => $this->getUrl(),
                'version' => $this->version,
                'clearHistory' => $this->resolveClearHistory(),
                'encryptHistory' => $this->encryptHistory,
            ],
            $this->resolveMergeProps(),
            $this->resolveDeferredProps(),
            $this->resolveCacheDirections(),
        );

        $this->body = $this->resolveBody($page);
    }

    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->props = array_merge($this->props, $key);
        } else {
            $this->props[$key] = $value;
        }

        return $this;
    }

    public function withViewData($key, $value = null): self
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    public function rootView(string $rootView): self
    {
        $this->rootView = $rootView;

        return $this;
    }

    public function cache(string|array $cacheFor): self
    {
        $this->cacheFor = is_array($cacheFor) ? $cacheFor : [$cacheFor];

        return $this;
    }

    /**
     * Resolve the properites for the response.
     */
    public function resolveProperties(array $props): array
    {
        $props = $this->resolvePartialProperties($props);
        $props = $this->resolveAlways($props);

        return $this->resolvePropertyInstances($props);
    }

    /**
     * Resolve the `only` and `except` partial request props.
     */
    public function resolvePartialProperties(array $props): array
    {
        if (!$this->isPartial()) {
            return array_filter($this->props, static function ($prop) {
                return !($prop instanceof IgnoreFirstLoad);
            });
        }

        $only = array_filter(explode(',', $this->request->headers->get(Header::PARTIAL_ONLY) ?? ''));
        $except = array_filter(explode(',', $this->request->headers->get(Header::PARTIAL_EXCEPT) ?? ''));

        if ($only !== []) {
            $newProps = [];

            foreach ($only as $key) {
                $value = Arr\get_by_key($props, $key);

                if ($value instanceof ArrayInterface) {
                    $value = $value->toArray();
                }

                $newProps = Arr\set_by_key($newProps, $key, $value);
            }

            $props = $newProps;
        }

        if ($except !== []) {
            Support\Arr\forget_keys($props, $except);
        }

        return $props;
    }

    /**
     * Resolve the `only` partial request props.
     */
    public function resolveOnly(array $props): array
    {
        $only = array_filter(explode(',', $this->request->headers->get(Header::PARTIAL_ONLY) ?? ''));

        $value = [];

        foreach ($only as $key) {
            Arr\set_by_key($value, $key, Support\Arr\data_get($props, $key));
        }

        return $value;
    }

    /**
     * Resolve the `except` partial request props.
     */
    public function resolveExcept(array $props): array
    {
        $except = array_filter(explode(',', $this->request->headers->get(Header::PARTIAL_EXCEPT) ?? ''));

        Support\Arr\forget_keys($props, $except);

        return $props;
    }

    /**
     * Resolve `always` properties that should always be included on all visits, regardless of "only" or "except" requests.
     */
    public function resolveAlways(array $props): array
    {
        $always = array_filter($this->props, static function ($prop) {
            return $prop instanceof AlwaysProp;
        });

        return array_merge($always, $props);
    }

    /**
     * Resolve all necessary class instances in the given props.
     */
    public function resolvePropertyInstances(array $props, bool $unpackDotProps = true): array
    {
        $result = [];

        foreach ($props as $key => $value) {
            if ($value instanceof Closure) {
                $value = invoke($value);
            } elseif (
                $value instanceof LazyProp ||
                    $value instanceof OptionalProp ||
                    $value instanceof DeferProp ||
                    $value instanceof AlwaysProp ||
                    $value instanceof MergeProp
            ) {
                $value = $value();
            } elseif ($value instanceof PromiseInterface) {
                $value = $value->wait();
            }

            if ($this->config->transform_pagination && $value instanceof PaginatedData) {
                $value = new PaginatorAdapter($value);
            }

            if ($value instanceof Arrayable || $value instanceof ArrayInterface) {
                $value = $value->toArray();
            } elseif ($value instanceof JsonSerializable) {
                $value = $value->jsonSerialize();
            }

            if ($value instanceof HttpResponse) {
                $value = $value->body;
            }

            if (is_array($value)) {
                $value = $this->resolvePropertyInstances($value, false);
            }

            if ($unpackDotProps && is_string($key) && str_contains($key, '.')) {
                $result = Arr\set_by_key($result, $key, $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Resolve the cache directions for the response.
     */
    public function resolveCacheDirections(): array
    {
        if ($this->cacheFor === []) {
            return [];
        }

        return [
            'cache' => array_map(function ($value) {
                if ($value instanceof DateInterval) {
                    $start = new DateTimeImmutable();
                    $end = $start->add($value);

                    return $end->getTimestamp() - $start->getTimestamp();
                }

                return intval($value);
            }, $this->cacheFor),
        ];
    }

    public function resolveMergeProps(): array
    {
        $resetProps = array_filter(explode(',', $this->request->headers->get(Header::RESET) ?? ''));
        $onlyProps = array_filter(explode(',', $this->request->headers->get(Header::PARTIAL_ONLY) ?? ''));
        $exceptProps = array_filter(explode(',', $this->request->headers->get(Header::PARTIAL_EXCEPT) ?? ''));

        $mergeProps = Arr\filter(
            $this->props,
            fn($prop, $key) => (
                $prop instanceof Mergeable &&
                $prop->shouldMerge() &&
                !in_array($key, $resetProps, true) &&
                ($onlyProps === [] || in_array($key, $onlyProps, true)) &&
                !in_array($key, $exceptProps, true)
            ),
        );

        $deepMergeProps = Arr\keys(Arr\filter($mergeProps, fn($prop) => $prop->shouldDeepMerge()));

        $matchPropsOn = Arr\values(Arr\flat_map($mergeProps, fn($prop, $key) => Arr\map_iterable(
            $prop->matchesOn(),
            fn($strategy) => $key . '.' . $strategy,
        )));

        $mergeProps = Arr\keys(Arr\filter($mergeProps, fn($prop) => !$prop->shouldDeepMerge()));

        return array_filter(
            [
                'mergeProps' => $mergeProps,
                'deepMergeProps' => $deepMergeProps,
                'matchPropsOn' => $matchPropsOn,
            ],
            fn($prop) => $prop !== [],
        );
    }

    public function resolveDeferredProps(): array
    {
        if ($this->isPartial()) {
            return [];
        }

        $deferredProps = Arr\map_iterable(
            Arr\group_by(
                Arr\map_iterable(Arr\filter($this->props, fn($prop) => $prop instanceof DeferProp), fn($prop, $key) => [
                    'key' => $key,
                    'group' => $prop->group(),
                ]),
                fn($item) => $item['group'],
            ),
            fn($group) => Arr\pluck($group, 'key'),
        );

        return Arr\is_empty($deferredProps) ? [] : ['deferredProps' => $deferredProps];
    }

    /**
     * Determine if the request is a partial request.
     */
    public function isPartial(): bool
    {
        return $this->request->headers->get(Header::PARTIAL_COMPONENT) === $this->component;
    }

    /**
     * Ensure the URL has a trailing slash before the query string (if it exists).
     */
    private function finishUrlWithTrailingSlash(string $url): string
    {
        $pathWithSlash = Str\ensure_ends_with(Str\before_first($url, '?'), '/');

        if (str_contains($url, '?')) {
            return $pathWithSlash . '?' . Str\after_first($url, '?');
        }

        return $pathWithSlash;
    }

    private function normalizeProps(array|ArrayInterface $props): array
    {
        return ($props instanceof ArrayInterface) ? $props->toArray() : $props;
    }

    private function resolveClearHistory(): bool
    {
        return $this->session->get('inertia.clear_history', $this->clearHistory);
    }

    private function resolveBody(array $page): array|null|InertiaView
    {
        if ($this->request->headers->has(Header::INERTIA)) {
            $inertiaVersion = $this->request->headers->get(Header::VERSION);
            $currentVersion = get(ResponseFactory::class)->getVersion();

            if ($this->request->method === Method::GET && $inertiaVersion && $inertiaVersion !== $currentVersion) {
                $this->setStatus(Status::CONFLICT);
                $this->addHeader(Header::LOCATION, $this->request->uri);

                return null;
            }

            $this->addHeader(Header::INERTIA, 'true');
            $this->setContentType(ContentType::JSON);

            return $page;
        }

        $ssr = $this->ssr($page);

        return new InertiaView($this->rootView, ['page' => $page], $ssr?->head, $ssr?->body);
    }

    private function ssr(array $page): ?SsrResponse
    {
        if (!$this->config->ssr->enabled) {
            return null;
        }

        return $this->gateway->dispatch($page);
    }

    private function getUrl(): string
    {
        if ($this->urlResolver instanceof Closure) {
            return invoke($this->urlResolver, ['request' => $this->request]);
        }

        $url = $this->request->uri;

        $prefix = $this->request->headers->get(Header::FORWARDED_PREFIX);

        if ($prefix) {
            $prefix = rtrim($prefix, '/');
            $url = $prefix . $url;
        }

        return $url;
    }
}
