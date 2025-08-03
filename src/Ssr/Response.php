<?php

declare(strict_types=1);

namespace Inertia\Ssr;

final readonly class Response
{
    /**
     * Prepare the Inertia Server Side Rendering (SSR) response.
     */
    public function __construct(
        public string $head,
        public string $body,
    ) {}
}
