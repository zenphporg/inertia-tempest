<?php

declare(strict_types=1);

use Inertia\Props\DeferProp;
use Inertia\Tests\TestCase;
use Tempest\Http\Request;

class DeferPropTest extends TestCase
{
    public function test_can_invoke(): void
    {
        $deferProp = new DeferProp(function () {
            return 'A deferred value';
        }, 'default');

        $this->assertSame('A deferred value', $deferProp());
        $this->assertSame('default', $deferProp->group());
    }

    public function test_can_invoke_and_merge(): void
    {
        $deferProp = new DeferProp(function () {
            return 'A deferred value';
        })->merge();

        $this->assertSame('A deferred value', $deferProp());
    }

    public function test_can_resolve_bindings_when_invoked(): void
    {
        $deferProp = new DeferProp(function (Request $request) {
            return $request;
        });

        $this->assertInstanceOf(Request::class, $deferProp());
    }
}
