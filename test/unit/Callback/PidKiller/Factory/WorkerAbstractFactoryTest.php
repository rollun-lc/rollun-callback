<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback\PidKiller\Factory;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use rollun\callback\PidKiller\Factory\WorkerAbstractFactory;
use rollun\callback\PidKiller\Worker;
use rollun\callback\PidKiller\WriterInterface;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;

class WorkerAbstractFactoryTest extends TestCase
{
    public function testInvoke()
    {
        $factory = new WorkerAbstractFactory();
        $requestedName = 'requestedName';
        $queue = 'queue';
        $callable = 'callable';
        $writer = 'writer';

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->expects($this->at(0))->method('get')->with('config')->willReturn([
            WorkerAbstractFactory::class => [
                $requestedName => [
                    WorkerAbstractFactory::KEY_QUEUE => $queue,
                    WorkerAbstractFactory::KEY_CALLABLE => $callable,
                    WorkerAbstractFactory::KEY_WRITER => $writer,
                ]
            ]
        ]);

        $container->expects($this->at(1))->method('get')->with($queue)->willReturn(
            QueueClientAbstractFactory::createSimpleQueueClient()
        );
        $container->expects($this->at(2))->method('get')->with($callable)->willReturn(function () {
        });
        $container->expects($this->at(3))->method('get')->with($writer)->willReturn(
            new class implements WriterInterface
            {
                public function write($data)
                {
                }
            }
        );

        $worker = $factory($container, $requestedName);
        $this->assertInstanceOf(Worker::class, $worker);
    }

    public function testCanCreate()
    {
        $factory = new WorkerAbstractFactory();
        $requestedName = 'requestedName';
        $queue = 'queue';
        $callable = 'callable';
        $writer = 'writer';

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->expects($this->at(0))->method('get')->with('config')->willReturn([
            WorkerAbstractFactory::class => [
                $requestedName => [
                    WorkerAbstractFactory::KEY_QUEUE => $queue,
                    WorkerAbstractFactory::KEY_CALLABLE => $callable,
                    WorkerAbstractFactory::KEY_WRITER => $writer,
                ]
            ]
        ]);

        $this->assertTrue($factory->canCreate($container, $requestedName));
    }
}
