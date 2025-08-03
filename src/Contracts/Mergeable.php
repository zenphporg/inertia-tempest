<?php

declare(strict_types=1);

namespace Inertia\Contracts;

interface Mergeable
{
    public function merge(): static;

    public function shouldMerge(): bool;
}
