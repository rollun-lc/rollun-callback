<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\SerializedCallback;

/**
 * Class CallbackTest
 */
class SerializedCallbackTest extends TestCase
{
    public function provider()
    {
        return [
            [[new A(), 'invoke']],
            ['Rollun\Test\Unit\Callback\A::staticInvoke'],
            ['Rollun\Test\Unit\Callback\invoke'],
            [
                fn($value) => $value,
            ],
            'nested callback' => [
                new SerializedCallback(fn($value) => $value)
            ],
            'two level nested callback' => [
                new SerializedCallback(new SerializedCallback(fn($value) => $value))
            ]
        ];
    }

    /**
     * @dataProvider provider
     * @param $callable
     */
    public function testInvoke($callable)
    {
        $callback = new SerializedCallback($callable);
        $this->assertEquals(1, $callback(1));
    }

    /**
     * @dataProvider provider
     * @param $callable
     */
    public function testSerialize($callable)
    {
        $callback = new SerializedCallback($callable);
        $this->assertEquals($callback(1), unserialize(serialize($callback))(1));
    }
}

class A
{
    public function __invoke($value)
    {
        return $value;
    }

    static public function staticInvoke($value)
    {
        return $value;
    }

    public function invoke($value)
    {
        return $value;
    }
}

function invoke($value)
{
    return $value;
}
