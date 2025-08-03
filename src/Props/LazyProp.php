<?php

declare(strict_types=1);

namespace Inertia\Props;

use Inertia\Contracts\IgnoreFirstLoad;

use function Tempest\invoke;

final class LazyProp implements IgnoreFirstLoad
{
    public function __construct(
        /**
         * @mago-expect strictness/require-parameter-type
         * @var callable
         */
        private $callback,
    ) {}

    public function __invoke(): mixed
    {
        return invoke($this->callback);
    }
}
