<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller\Factory;

use Psr\Container\ContainerInterface;
use rollun\callback\PidKiller\Worker;
use rollun\callback\PidKiller\WorkerManager;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Config example:
 *
 *  [
 *      WorkerManagerAbstractFactory::class => [
 *          'requestedName1' => [
 *              'tableGateway' => 'tableGatewayServiceName',
 *              'process' => 'processServiceName',
 *              'workerManagerName' => 'very-important-worker-manager'
 *              'processCount' => 4
 *          ]
 *          'requestedName2' => [
 *              // ...
 *          ]
 *      ]
 *  ]
 *
 * Class WorkerAbstractFactory
 * @package rollun\callback\PidKiller\Factory
 */
class WorkerManagerAbstractFactory implements AbstractFactoryInterface
{
    public const KEY_CLASS = 'class';

    public const DEFAULT_CLASS = WorkerManager::class;

    public const KEY_TABLE_GATEWAY = 'tableGateway';

    public const KEY_PROCESS = 'process';

    public const KEY_WORKER_MANAGER_NAME = 'workerManagerName';

    public const KEY_PROCESS_COUNT = 'processCount';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $options ?? $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_TABLE_GATEWAY])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_TABLE_GATEWAY . "'");
        }

        if (!isset($serviceConfig[self::KEY_PROCESS])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_PROCESS . "'");
        }

        if (!isset($serviceConfig[self::KEY_WORKER_MANAGER_NAME])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_WORKER_MANAGER_NAME . "'");
        }

        if (!isset($serviceConfig[self::KEY_PROCESS_COUNT])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_PROCESS_COUNT . "'");
        }

        $tableGateway = is_string($serviceConfig[self::KEY_TABLE_GATEWAY]) ? $container->get($serviceConfig[self::KEY_TABLE_GATEWAY]) : $serviceConfig[self::KEY_TABLE_GATEWAY];
        $process = is_string($serviceConfig[self::KEY_PROCESS]) ? $container->get($serviceConfig[self::KEY_PROCESS]) : $serviceConfig[self::KEY_PROCESS];
        $workerManagerName = $serviceConfig[self::KEY_WORKER_MANAGER_NAME];
        $processCount = $serviceConfig[self::KEY_PROCESS_COUNT];
        $class = $serviceConfig[self::KEY_CLASS];

        return new $class($tableGateway, $process, $workerManagerName, $processCount);
    }

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $class = $container->get('config')[self::class][$requestedName][self::KEY_CLASS] ?? null;

        if (is_a($class, self::DEFAULT_CLASS, true)) {
            return true;
        }

        return false;
    }
}
