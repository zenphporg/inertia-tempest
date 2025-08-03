<?php

declare(strict_types=1);

namespace Inertia\Ssr\Contracts;

use Inertia\Ssr\Response;

interface Gateway
{
    public function dispatch(array $page): ?Response;
}
