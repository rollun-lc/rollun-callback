<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\Callback;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use Zend\ServiceManager\ServiceManager;

class MultiplexerTest extends CallbackTestDataProvider
{
    /**
     * @var ServiceManager
     */
    protected $container;

    protected function getContainer(): ServiceManager
    {
        if ($this->container === null) {
            $this->container = require 'config/container.php';
        }

        return $this->container;
    }

    /**
     * @dataProvider providerMultiplexerType
     * @param array $callbacks
     * @param $val
     */
    public function test(array $callbacks, $val)
    {
        $multiplexer = new Multiplexer($this->getContainer()->get(LoggerInterface::class), $callbacks);
        $result = $multiplexer($val);
        $payload = $result instanceof PayloadInterface ? $result->getPayload() : $result;
        $this->assertTrue(isset($payload));
    }
}
