<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback\PidKiller\Factory;

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\PidKiller\Factory\WorkerManagerAbstractFactory;
use rollun\callback\PidKiller\WorkerManager;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;

class WorkerManagerAbstractFactoryTest extends TestCase
{
    public function testInvoke()
    {
        $factory = new WorkerManagerAbstractFactory();
        $requestedName = 'requestedName';
        $tableGateway = 'tableGateway';
        $process = 'process';
        $workerManagerName = 'testName';
        $processCount = 4;

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->expects($this->at(0))->method('get')->with('config')->willReturn([
            WorkerManagerAbstractFactory::class => [
                $requestedName => [
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => $tableGateway,
                    WorkerManagerAbstractFactory::KEY_PROCESS => $process,
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => $workerManagerName,
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => $processCount,
                ],
            ],
        ]);

        $container->expects($this->at(1))->method('get')->with($tableGateway)->willReturn(new TableGateway('test',
            new Adapter([
                'driver' => getenv('DB_DRIVER'),
                'database' => getenv('DB_NAME'),
                'username' => getenv('DB_USER'),
                'password' => getenv('DB_PASS'),
                'hostname' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT'),
            ])));
        $container->expects($this->at(2))->method('get')->with($process)->willReturn(new Process(function () {
        }, null));

        $worker = $factory($container, $requestedName);
        $this->assertTrue($worker instanceof WorkerManager);
    }

    public function testCanCreateSuccess()
    {
        $factory = new WorkerManagerAbstractFactory();
        $requestedName = 'requestedName';
        $tableGateway = 'tableGateway';
        $process = 'process';
        $workerManagerName = 'testName';
        $processCount = 4;

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->expects($this->at(0))->method('get')->with('config')->willReturn([
            WorkerManagerAbstractFactory::class => [
                $requestedName => [
                    WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => $tableGateway,
                    WorkerManagerAbstractFactory::KEY_PROCESS => $process,
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => $workerManagerName,
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => $processCount,
                ],
            ],
        ]);

        $this->assertTrue($factory->canCreate($container, $requestedName));
    }

    public function testCanCreateFalse()
    {
        $factory = new WorkerManagerAbstractFactory();
        $requestedName = 'requestedName';
        $tableGateway = 'tableGateway';
        $process = 'process';
        $workerManagerName = 'testName';
        $processCount = 4;

        /** @var ContainerInterface|MockObject $container */
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $container->expects($this->at(0))->method('get')->with('config')->willReturn([
            WorkerManagerAbstractFactory::class => [
                $requestedName => [
                    WorkerManagerAbstractFactory::KEY_CLASS => self::class,
                    WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => $tableGateway,
                    WorkerManagerAbstractFactory::KEY_PROCESS => $process,
                    WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => $workerManagerName,
                    WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => $processCount,
                ],
            ],
        ]);

        $this->assertFalse($factory->canCreate($container, $requestedName));
    }
}
