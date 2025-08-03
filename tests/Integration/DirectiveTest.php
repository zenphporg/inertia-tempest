<?php

declare(strict_types=1);

use Inertia\Configs\InertiaConfig;
use Inertia\Ssr\Contracts\Gateway;
use Inertia\Ssr\Response;
use Inertia\Tests\Fixtures\FakeGateway;
use Inertia\Tests\TestCase;
use Inertia\Views\InertiaView;

class DirectiveTest extends TestCase
{
    public function test_inertia_method_renders_the_root_element(): void
    {
        $view = new InertiaView(
            path: 'inertia.view.php',
            inertia: ['page' => self::EXAMPLE_PAGE_OBJECT],
            ssrHead: null,
            ssrBody: null,
        );

        $expectedHtml = '<div id="app" data-page="{&quot;component&quot;:&quot;Foo\/Bar&quot;,&quot;props&quot;:{&quot;foo&quot;:&quot;bar&quot;},&quot;url&quot;:&quot;\/test&quot;,&quot;version&quot;:&quot;&quot;,&quot;encryptHistory&quot;:false,&quot;clearHistory&quot;:false}"></div>';

        $this->assertSame($expectedHtml, (string) $view->inertia());
    }

    public function test_inertia_directive_renders_server_side_rendered_content_when_enabled(): void
    {
        $config = $this->container->get(InertiaConfig::class);
        $originalValue = $config->ssr->enabled;
        $config->ssr->enabled = true;

        $ssrResponse = new Response(
            head: '<title>SSR Head</title>',
            body: '<p>This is some example SSR content</p>',
        );
        $mockGateway = Mockery::mock(Gateway::class)
            ->shouldReceive('dispatch')
            ->once()
            ->andReturn($ssrResponse)
            ->getMock();
        $this->container->singleton(Gateway::class, fn() => $mockGateway);

        try {
            $response = $this->factory->render('User/Edit', self::EXAMPLE_PAGE_OBJECT['props']);
            $renderedHtml = (string) $response->body->inertia();

            $this->assertSame('<p>This is some example SSR content</p>', $renderedHtml);
        } finally {
            $config->ssr->enabled = $originalValue;
        }
    }

    public function test_inertia_directive_can_use_a_different_root_element_id(): void
    {
        $config = $this->container->get(InertiaConfig::class);
        $originalValue = $config->ssr->enabled;
        $config->ssr->enabled = false;

        try {
            $response = $this->factory->render('Foo/Bar', self::EXAMPLE_PAGE_OBJECT['props']);
            $view = $response->body;

            $expectedHtml = '<div id="foo" data-page="{&quot;component&quot;:&quot;Foo\/Bar&quot;,&quot;props&quot;:{&quot;foo&quot;:&quot;bar&quot;},&quot;url&quot;:&quot;\/&quot;,&quot;version&quot;:&quot;&quot;,&quot;clearHistory&quot;:false,&quot;encryptHistory&quot;:false}"></div>';

            $this->assertSame($expectedHtml, (string) $view->inertia('foo'));
        } finally {
            $config->ssr->enabled = $originalValue;
        }
    }

    public function test_inertia_head_renders_nothing_when_ssr_is_disabled(): void
    {
        $view = new InertiaView(
            path: 'inertia.view.php',
            inertia: ['page' => self::EXAMPLE_PAGE_OBJECT],
            ssrHead: null,
            ssrBody: null,
        );

        $this->assertEmpty((string) $view->inertiaHead());
    }

    public function test_inertia_head_renders_ssr_head_when_enabled(): void
    {
        $view = new InertiaView(
            path: 'inertia.view.php',
            inertia: ['page' => self::EXAMPLE_PAGE_OBJECT],
            ssrHead: '<title inertia>Example SSR Title</title>',
            ssrBody: '',
        );

        $this->assertSame('<title inertia>Example SSR Title</title>', (string) $view->inertiaHead());
    }

    public function test_the_server_side_rendering_request_is_dispatched_only_once_per_request(): void
    {
        $config = $this->container->get(InertiaConfig::class);
        $originalValue = $config->ssr->enabled;
        $config->ssr->enabled = true;

        $gateway = new FakeGateway();
        $this->container->singleton(Gateway::class, fn() => $gateway);

        try {
            $response = $this->factory->render('User/Edit', self::EXAMPLE_PAGE_OBJECT['props']);

            $head = (string) $response->body->inertiaHead();
            $body = (string) $response->body->inertia();

            $this->assertSame(1, $gateway->times);
            $this->assertSame('<title inertia>Example SSR Title</title>', $head);
            $this->assertSame('<p>This is some example SSR content</p>', $body);
        } finally {
            $config->ssr->enabled = $originalValue;
        }
    }
}
