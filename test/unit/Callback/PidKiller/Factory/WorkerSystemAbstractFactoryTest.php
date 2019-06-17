<?php

namespace rollun\test\unit\Callback\PidKiller\Factory;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use rollun\callback\PidKiller\Factory\WorkerAbstractFactory;
use rollun\callback\PidKiller\Factory\WorkerSystemAbstractFactory;
use rollun\callback\PidKiller\Worker;
use rollun\callback\PidKiller\WorkerManager;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use Zend\Db\TableGateway\TableGateway;

class WorkerSystemAbstractFactoryTest extends \PHPUnit\Framework\TestCase
{

    public function testInvoke()
    {
        $factory = new WorkerSystemAbstractFactory();

        $testWorkerSystem = 'testSystem';
        $callable = 'testCallable';
        $queue = 'testQueue';

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();

        //Add config
        $container->expects($this->at(0))->method('get')->with('config')->willReturn([
            WorkerSystemAbstractFactory::class => [
                $testWorkerSystem => [
                    WorkerAbstractFactory::KEY_QUEUE => $queue,
                    WorkerAbstractFactory::KEY_CALLABLE => $callable,
                ]
            ]
        ]);

        $container->expects($this->at(1))->method('get')->with($queue)->willReturn(
            QueueClientAbstractFactory::createSimpleQueueClient()
        );
        $container->expects($this->at(2))->method('get')->with($callable)->willReturn(function () {
        });

        /** @var TableGateway|MockObject $container */
        $tableGateway = $this->getMockBuilder(TableGateway::class)->disableOriginalConstructor()->getMock();

        //need be 4.
        $container->expects($this->at(4))->method('get')
            ->with(WorkerSystemAbstractFactory::DEFAULT_TABLE_GATEWAY)->willReturn($tableGateway);


        $worker = $factory($container, $testWorkerSystem);
        $this->assertInstanceOf(WorkerManager::class, $worker);
    }
}
