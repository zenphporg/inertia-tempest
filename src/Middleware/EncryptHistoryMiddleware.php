<?php

declare(strict_types=1);

namespace Inertia\Middleware;

use Inertia\ResponseFactory;
use Override;
use Tempest\Discovery\SkipDiscovery;
use Tempest\Http\Request;
use Tempest\Http\Response;
use Tempest\Router\HttpMiddleware;
use Tempest\Router\HttpMiddlewareCallable;

#[SkipDiscovery]
readonly class EncryptHistoryMiddleware implements HttpMiddleware
{
    public function __construct(
        private ResponseFactory $inertia,
    ) {}

    #[Override]
    public function __invoke(Request $request, HttpMiddlewareCallable $next): Response
    {
        $this->inertia->encryptHistory();

        return $next($request);
    }
}
