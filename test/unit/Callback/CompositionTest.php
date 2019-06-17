<?php

namespace rollun\test\unit\Callback;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Composition;

class CompositionTest extends TestCase
{

    public function test__invoke()
    {
        $composition = new Composition(
            function ($v) {
                return $v + 1;
            },
            function ($v) {
                return $v + 2;
            },
            function ($v) {
                return $v + 5;
            }
        );

        $this->assertEquals(8, $composition(0));

    }
}
