<?php

declare(strict_types=1);

namespace Inertia\Tests\Fixtures;

use Inertia\Ssr\Contracts\Gateway;
use Inertia\Ssr\Response;
use Override;
use Tempest\Discovery\SkipDiscovery;

#[SkipDiscovery]
final class FakeGateway implements Gateway
{
    public int $times = 0;

    #[Override]
    public function dispatch(array $page): ?Response
    {
        $this->times++;

        return new Response(
            head: '<title inertia>Example SSR Title</title>',
            body: '<p>This is some example SSR content</p>',
        );
    }
}
