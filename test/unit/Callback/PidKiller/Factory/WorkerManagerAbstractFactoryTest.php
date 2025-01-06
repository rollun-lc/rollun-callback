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
        if (getenv("DB_DRIVER") === false) {
            $this->markTestIncomplete('Needs DB for running');
        }

        $requestedName = 'requestedName';
        $tableGateway = 'tableGateway';
        $process = 'process';

        $container = self::createContainer([
            'config' => [
                WorkerManagerAbstractFactory::class => [
                    $requestedName => [
                        WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                        WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => $tableGateway,
                        WorkerManagerAbstractFactory::KEY_PROCESS => $process,
                        WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'testName',
                        WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    ],
                ],
            ],
            $tableGateway => new TableGateway('test', new Adapter([
                'driver' => getenv('DB_DRIVER'),
                'database' => getenv('DB_NAME'),
                'username' => getenv('DB_USER'),
                'password' => getenv('DB_PASS'),
                'hostname' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT'),
            ])),
            $process => new Process(function () {}, null),
        ]);

        $worker = (new WorkerManagerAbstractFactory())($container, $requestedName);
        $this->assertTrue($worker instanceof WorkerManager);
    }

    public function testCanCreateSuccess()
    {
        $requestedName = 'requestedName';

        $container = self::createContainer([
            'config' => [
                WorkerManagerAbstractFactory::class => [
                    $requestedName => [
                        WorkerManagerAbstractFactory::KEY_CLASS => WorkerManager::class,
                        WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'tableGateway',
                        WorkerManagerAbstractFactory::KEY_PROCESS => 'process',
                        WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'testName',
                        WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    ],
                ],
            ],
        ]);

        $this->assertTrue((new WorkerManagerAbstractFactory())->canCreate($container, $requestedName));
    }

    public function testCanCreateFalse()
    {
        $requestedName = 'requestedName';

        $container = self::createContainer([
            'config' => [
                WorkerManagerAbstractFactory::class => [
                    $requestedName => [
                        WorkerManagerAbstractFactory::KEY_CLASS => self::class, // wrong class (not WorkerManager)
                        WorkerManagerAbstractFactory::KEY_TABLE_GATEWAY => 'tableGateway',
                        WorkerManagerAbstractFactory::KEY_PROCESS => 'process',
                        WorkerManagerAbstractFactory::KEY_WORKER_MANAGER_NAME => 'testName',
                        WorkerManagerAbstractFactory::KEY_PROCESS_COUNT => 4,
                    ],
                ],
            ],
        ]);

        $this->assertFalse((new WorkerManagerAbstractFactory())->canCreate($container, $requestedName));
    }

    private static function createContainer(array $config): ContainerInterface
    {
        return new class ($config) implements ContainerInterface {
            public function __construct(private array $config)
            {
            }

            public function get(string $id): mixed
            {
                return $this->config[$id];
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->config[]);
            }
        };
    }
}
