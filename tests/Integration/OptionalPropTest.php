<?php

declare(strict_types=1);

use Inertia\Props\OptionalProp;
use Inertia\Tests\TestCase;
use Tempest\Http\Request;

class OptionalPropTest extends TestCase
{
    public function test_can_invoke(): void
    {
        $optionalProp = new OptionalProp(function () {
            return 'A lazy value';
        });

        $this->assertSame('A lazy value', $optionalProp());
    }

    public function test_can_resolve_bindings_when_invoked(): void
    {
        $optionalProp = new OptionalProp(function (Request $request) {
            return $request;
        });

        $this->assertInstanceOf(Request::class, $optionalProp());
    }
}
