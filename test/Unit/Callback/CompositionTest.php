<?php

namespace Rollun\Test\Unit\Callback;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Composition;

class CompositionTest extends TestCase
{

    public function test__invoke()
    {
        $composition = new Composition(
            fn($v) => $v + 1,
            fn($v) => $v + 2,
            fn($v) => $v + 5
        );

        $this->assertEquals(8, $composition(0));

    }
}
