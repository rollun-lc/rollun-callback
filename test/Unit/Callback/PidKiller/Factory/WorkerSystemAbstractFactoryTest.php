<?php

namespace Rollun\Test\Unit\Callback\PidKiller\Factory;

use Jaeger\Tracer\Tracer;
use Jaeger\Tracer\TracerInterface;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rollun\callback\PidKiller\Factory\WorkerAbstractFactory;
use rollun\callback\PidKiller\Factory\WorkerSystemAbstractFactory;
use rollun\callback\PidKiller\WorkerManager;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use Laminas\Db\TableGateway\TableGateway;

class WorkerSystemAbstractFactoryTest extends \PHPUnit\Framework\TestCase
{

    public function testInvoke()
    {
        /** @var TableGateway|MockObject $container */
        $tableGateway = $this->getMockBuilder(TableGateway::class)->disableOriginalConstructor()->getMock();

        $testWorkerSystem = 'testSystem';
        $callable = 'testCallable';
        $queue = 'testQueue';


        $containerConfig = [
            LoggerInterface::class => new NullLogger(),
            Tracer::class => $this->getMockBuilder(Tracer::class)->disableOriginalConstructor()->getMock(),
            'config' => [
                WorkerSystemAbstractFactory::class => [
                    $testWorkerSystem => [
                        WorkerAbstractFactory::KEY_QUEUE => $queue,
                        WorkerAbstractFactory::KEY_CALLABLE => $callable,
                    ]
                ],
            ],
            $queue => QueueClientAbstractFactory::createSimpleQueueClient(),
            $callable => static function () {},
            WorkerSystemAbstractFactory::DEFAULT_TABLE_GATEWAY => $tableGateway
        ];

        $container = new class ($containerConfig) implements ContainerInterface {
            public function __construct(private array $config) {}

            public function get(string $id): mixed
            {
                return $this->config[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->config[]);
            }
        };

        $worker = (new WorkerSystemAbstractFactory())($container, $testWorkerSystem);
        $this->assertInstanceOf(WorkerManager::class, $worker);
    }
}
