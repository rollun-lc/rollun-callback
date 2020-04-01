<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use Zend\ServiceManager\ServiceManager;

class MultiplexerTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $container;

    protected function getContainer(): ServiceManager
    {
        global $container;
        if ($this->container === null) {
            $this->container = $container;
        }

        return $this->container;
    }

    public function provider()
    {
        $stdObject = (object)['prop' => 'Hello '];

        return [
            [
                [
                    new Process(
                        function ($val) {
                            return 'Hello ' . $val;
                        }
                    ),
                    new Process(
                        function ($val) use ($stdObject) {
                            return $stdObject->prop . $val;
                        }
                    ),
                    new Process($this->getContainer()->get('testCallback')),
                ],
                "World",
            ],
            [
                [
                    new Process(
                        function ($val) {
                            return 'Hello ' . $val;
                        }
                    ),
                    new Process(
                        function ($val) use ($stdObject) {
                            throw new \Exception("some error");
                        }
                    ),
                    new Process($this->getContainer()->get('testCallback')),
                ],
                "World",
            ],
        ];
    }


    /**
     * @dataProvider provider
     * @param array $callbacks
     * @param $val
     */
    public function testInvoke($callbacks, $val)
    {
        $multiplexer = new Multiplexer($this->getContainer()->get(LoggerInterface::class), $callbacks);
        $result = $multiplexer($val);
        $payload = $result instanceof PayloadInterface ? $result->getPayload() : $result;
        $this->assertTrue(isset($payload));
    }

    public function testInvokeWithCallable()
    {
        $multiplexer = new Multiplexer(
            $this->getContainer()->get(LoggerInterface::class), [
                function ($value) {
                    return $value + 1;
                },
                function ($value) {
                    return $value + 2;
                },
            ]
        );

        $this->assertEquals($multiplexer(1), [2, 3]);
    }

    public function testInvokableWithInterrupter()
    {
        $multiplexer = new Multiplexer(
            $this->getContainer()->get(LoggerInterface::class), [
                new Process(function ($value) {
                    return $value + 1;
                }),
                new Process(function ($value) {
                    return $value + 2;
                }),
            ]
        );

        $this->assertTrue($multiplexer() instanceof PayloadInterface);
    }
}
