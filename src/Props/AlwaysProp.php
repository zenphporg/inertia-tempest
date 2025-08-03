<?php

declare(strict_types=1);

namespace Inertia\Props;

use Closure;

use function Tempest\invoke;

final readonly class AlwaysProp
{
    public function __construct(
        private mixed $value,
    ) {}

    public function __invoke(): mixed
    {
        if (!is_callable($this->value)) {
            return $this->value;
        }

        if ($this->value instanceof Closure) {
            return invoke($this->value);
        }

        return call_user_func($this->value);
    }
}
