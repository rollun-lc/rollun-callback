<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback\PidKiller\Factory;

use Jaeger\Tracer\Tracer;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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

        $containerConfig = [
            LoggerInterface::class => new NullLogger(),
            Tracer::class => $this->getMockBuilder(Tracer::class)->disableOriginalConstructor()->getMock(),
            'config' => [
                WorkerAbstractFactory::class => [
                    $requestedName => [
                        WorkerAbstractFactory::KEY_QUEUE => $queue,
                        WorkerAbstractFactory::KEY_CALLABLE => $callable,
                        WorkerAbstractFactory::KEY_WRITER => $writer,
                    ]
                ],
            ],
            $queue => QueueClientAbstractFactory::createSimpleQueueClient(),
            $callable => static function () {},
            $writer => new class implements WriterInterface
            {
                public function write($data)
                {
                }
            },
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

        $worker = $factory($container, $requestedName);
        $this->assertInstanceOf(Worker::class, $worker);
    }

    public function testCanCreate()
    {
        $requestedName = 'requestedName';

        $containerConfig = [
            LoggerInterface::class => new NullLogger(),
            Tracer::class => $this->getMockBuilder(Tracer::class)->disableOriginalConstructor()->getMock(),
            'config' => [
                WorkerAbstractFactory::class => [
                    $requestedName => [
                        WorkerAbstractFactory::KEY_QUEUE => 'queue',
                        WorkerAbstractFactory::KEY_CALLABLE => 'callable',
                        WorkerAbstractFactory::KEY_WRITER => 'writer',
                    ]
                ]
            ],
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

        $this->assertTrue((new WorkerAbstractFactory())->canCreate($container, $requestedName));
    }
}
