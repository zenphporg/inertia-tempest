<?php

declare(strict_types=1);

namespace Inertia\Ssr;

use Inertia\Ssr\Contracts\Gateway;
use Override;
use Tempest\Container\Container;
use Tempest\Container\Initializer;
use Tempest\Container\Singleton;

#[Singleton]
final readonly class GatewayInitializer implements Initializer
{
    #[Override]
    public function initialize(Container $container): Gateway
    {
        return $container->get(HttpGateway::class);
    }
}
