<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\Callback;

use rollun\callback\Callback\SerializedCallback;

/**
 * Class CallbackTest
 * @package rollun\test\Callback
 */
class CallbackTest extends CallbackTestDataProvider
{
    /**
     * @covers \rollun\callback\Callback\SerializedCallback::__invoke
     * @dataProvider providerMainType
     */
    public function testInvoke($callable, $val, $expected)
    {
        $callback = new SerializedCallback($callable);
        /* @var $callback SerializedCallback */
        $this->assertEquals($expected, $callback($val));
    }

    /**
     * @covers \rollun\callback\Callback\SerializedCallback::__sleep
     * @dataProvider providerMainType()
     */
    public function testSleep($callable, $val, $expected)
    {
        $callback = new SerializedCallback($callable);
        /* @var $callback SerializedCallback */
        $this->assertEquals('array', gettype($callback->__sleep()));
    }

    /**
     * @covers \rollun\callback\Callback\SerializedCallback::__wakeup
     * @dataProvider providerMainType()
     */
    public function testWakeup($callable, $val, $expected)
    {
        $callback = new SerializedCallback($callable);
        /* @var $callback SerializedCallback */
        $wakeupedCallback = unserialize(serialize($callback));
        $this->assertEquals($expected, $wakeupedCallback($val));
    }
}
