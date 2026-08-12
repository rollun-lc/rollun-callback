<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback;

use PHPUnit\Framework\TestCase;
use Rollun\Test\Support\RequiresWorkingOpisClosure;
use rollun\callback\Callback\SerializedCallback;

/**
 * Class CallbackTest
 */
class SerializedCallbackTest extends TestCase
{
    use RequiresWorkingOpisClosure;

    /**
     * The second value of every set tells whether that callback kind reaches opis/closure
     * on serialization: closures do, and so do string and array callables, because
     * SerializedCallback converts them into closures.
     */
    public function provider()
    {
        return [
            [[new A(), 'invoke'], true],
            ['Rollun\Test\Unit\Callback\A::staticInvoke', true],
            ['Rollun\Test\Unit\Callback\invoke', true],
            [
                fn($value) => $value,
                true,
            ],
            // The nested sets are about nesting, not about the callback kind, so they use an
            // invokable object: it is serialized natively and keeps them clear of opis/closure.
            'nested callback' => [
                new SerializedCallback(new A()),
                false,
            ],
            'two level nested callback' => [
                new SerializedCallback(new SerializedCallback(new A())),
                false,
            ],
        ];
    }

    /**
     * @dataProvider provider
     * @param $callable
     */
    public function testInvoke($callable, bool $reachesOpisClosure)
    {
        $callback = new SerializedCallback($callable);
        $this->assertEquals(1, $callback(1));
    }

    /**
     * @dataProvider provider
     * @param $callable
     */
    public function testSerialize($callable, bool $reachesOpisClosure)
    {
        if ($reachesOpisClosure) {
            $this->skipIfPhpBreaksOpisClosure();
        }

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

    public static function staticInvoke($value)
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
