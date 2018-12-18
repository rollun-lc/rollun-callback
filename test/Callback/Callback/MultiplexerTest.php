<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\callback\Callback;

use rollun\callback\Callback\Multiplexer;

class MultiplexerTest extends CallbackTestDataProvider
{
    /**
     * @dataProvider providerMultiplexerType
     * @param array $interrupters
     * @param $val
     * @throws \ReflectionException
     */
    public function test(array $interrupters, $val)
    {

        $multiplexer = new Multiplexer($interrupters);
        $result = $multiplexer($val);
        $this->assertTrue(isset($result['data']));
    }
}
