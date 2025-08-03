<?php

declare(strict_types=1);

namespace Inertia\Tests\Fixtures;

use Inertia\Middleware\EncryptHistoryMiddleware;
use Inertia\Middleware\Middleware;
use Inertia\Response;
use Tempest\Http\Request;
use Tempest\Http\Responses\Redirect;
use Tempest\Router\Get;
use Tempest\Router\Put;
use Tempest\Support\Arr\ImmutableArray;

final class TestController
{
    public static bool $voidActionCalled = false;

    #[Get('/basic-render-test')]
    public function basicRender(): Response
    {
        return inertia()->render('User/Edit');
    }

    #[Get('/basic-render-with-middleware-test', middleware: [Middleware::class])]
    public function basicRenderWithMiddleware(): Response
    {
        return inertia()->render('User/Edit');
    }

    #[Get('/basic-render-with-example-middleware-test', middleware: [ExampleMiddleware::class])]
    public function basicRenderWithExampleMiddleware(): Response
    {
        return inertia()->render('User/Edit');
    }

    #[Get('/version-test-with-helper', middleware: [ExampleMiddleware::class])]
    public function versionCanBeAClosure(): Response
    {
        inertia()->version(fn() => 'test-version-from-closure');

        return inertia()->render('User/Edit', [
            'foo' => 'bar',
        ]);
    }

    #[Get('/custom-url-resolver')]
    public function customUrlResolver(): Response
    {
        inertia()->resolveUrlUsing(fn() => '/my-custom-url');

        return inertia()->render('User/Edit');
    }

    #[Get('/shared-data-test')]
    public function sharedData(): Response
    {
        inertia()->share('foo', 'bar');

        return inertia()->render('User/Edit');
    }

    #[Get('/dot-prop-merging-test')]
    public function dotPropMerging(): Response
    {
        inertia()->share('auth.user', [
            'name' => 'Jonathan',
        ]);

        return inertia()->render('User/Edit', [
            'auth.user.can.create_group' => false,
        ]);
    }

    #[Get('/shared-closure-test')]
    public function sharedClosure(): Response
    {
        inertia()->share('query', fn(Request $request) => $request->query);

        return inertia()->render('User/Edit');
    }

    #[Get('/dot-prop-callbacks-merging-test')]
    public function dotPropsWithCallbacksMerging(): Response
    {
        inertia()->share('auth.user', fn() => [
            'name' => 'Jonathan',
        ]);

        return inertia()->render('User/Edit', [
            'auth.user.can.create_group' => false,
        ]);
    }

    #[Get('/arrayable-props-test')]
    public function arrayableProps(): Response
    {
        inertia()->share('foo', 'this will be overwritten');

        $props = new ImmutableArray([
            'foo' => 'bar',
        ]);

        return inertia()->render('User/Edit', $props);
    }

    #[Put('/void-put-route')]
    public function voidPutAction(): void
    {
        self::$voidActionCalled = true;
    }

    #[Get('/custom-empty-response', middleware: [ExampleMiddleware::class])]
    public function customEmptyResponseAction(): void
    {
        // This action intentionally returns nothing.
    }

    #[Get('/numeric-version-test')]
    public function numericVersion(): Response
    {
        inertia()->version(1597347897973);

        return inertia()->render('User/Edit');
    }

    #[Get('/string-version-test')]
    public function stringVersion(): Response
    {
        inertia()->version('foo-version');

        return inertia()->render('User/Edit');
    }

    #[Get('/overwrite-errors-test')]
    public function overwriteErrorsProp(): Response
    {
        return inertia()->render('User/Edit', [
            'errors' => 'foo',
        ]);
    }

    #[Get('/encrypt-history-test')]
    public function encryptHistory(): Response
    {
        inertia()->encryptHistory();

        return inertia()->render('User/Edit');
    }

    #[Get('/encrypt-history-middleware-test', middleware: [EncryptHistoryMiddleware::class])]
    public function encryptHistoryWithMiddleware(): Response
    {
        return inertia()->render('User/Edit');
    }

    #[Get('/encrypt-history-override-test')]
    public function encryptHistoryOverride(): Response
    {
        inertia()->encryptHistory(false);

        return inertia()->render('User/Edit');
    }

    #[Get('/clear-history-test')]
    public function clearHistory(): Response
    {
        inertia()->clearHistory();

        return inertia()->render('User/Edit');
    }

    #[Get('/clear-history-and-redirect')]
    public function clearHistoryAndRedirect(): Redirect
    {
        inertia()->clearHistory();

        return new Redirect('/users-after-redirect');
    }

    #[Get('/users-after-redirect')]
    public function usersAfterRedirect(): Response
    {
        return inertia()->render('User/Edit');
    }
}
