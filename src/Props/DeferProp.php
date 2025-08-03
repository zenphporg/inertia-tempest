<?php

declare(strict_types=1);

namespace Inertia\Props;

use Inertia\Contracts\IgnoreFirstLoad;
use Inertia\Contracts\Mergeable;
use Inertia\Traits\MergesProps;

use function Tempest\invoke;

final class DeferProp implements IgnoreFirstLoad, Mergeable
{
    use MergesProps;

    public function __construct(
        /**
         * @mago-expect strictness/require-parameter-type
         * @var callable
         */
        private $callback,
        private readonly string $group = 'default',
    ) {}

    public function group(): ?string
    {
        return $this->group;
    }

    public function __invoke(): mixed
    {
        return invoke($this->callback);
    }
}
