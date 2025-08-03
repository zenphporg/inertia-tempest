<?php

declare(strict_types=1);

namespace Inertia\Props;

use Inertia\Contracts\Mergeable;
use Inertia\Traits\MergesProps;

use function Tempest\invoke;

class MergeProp implements Mergeable
{
    use MergesProps;

    public function __construct(
        protected mixed $value,
    ) {
        $this->merge = true;
    }

    public function __invoke(): mixed
    {
        return is_callable($this->value) ? invoke($this->value) : $this->value;
    }
}
