<?php

declare(strict_types=1);

namespace Inertia\Ssr\Contracts;

interface HasHealthCheck
{
    public function isHealthy(): bool;
}
