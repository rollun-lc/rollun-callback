<?php

namespace Rollun\Test\Unit\Callback\PidKiller;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\InterrupterInterface;
use rollun\callback\PidKiller\ProcessManager;
use rollun\callback\PidKiller\WorkerManager;
use PHPUnit\Framework\TestCase;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
use Laminas\Db\TableGateway\TableGateway;

class WorkerManagerTest extends TestCase
{

    /**
     * @throws \ReflectionException
     */
    public function test__invoke()
    {
        /**
         * @var $slotTable TableGateway|MockObject
         */
        $slotTable = $this->getMockBuilder(TableGateway::class)->disableOriginalConstructor()->getMock();

        /**
         * @var $procesManager ProcessManager|MockObject
         */
        $procesManager = $this->getMockBuilder(ProcessManager::class)->getMock();

        /**
         * @var $logger LoggerInterface|MockObject
         */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $callCount = 0;
        $interrupter = new class() implements InterrupterInterface
        {
            private static $callCount = 0;

            /**
             * @param $value
             * @return mixed
             */
            public function __invoke($value): PayloadInterface
            {
                ++self::$callCount;
                return new SimplePayload(uniqid('test_', false), ['callCount' => self::$callCount]);
            }
        };

        $workerManager = new WorkerManager(
            $slotTable,
            $interrupter,
            'TestWorkerManager',
            5,
            $procesManager,
            $logger
        );

        $this->assertTrue(true);
    }
}
