<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller\Factory;

use Interop\Container\ContainerInterface;
use rollun\callback\PidKiller\Worker;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Config example:
 *
 *  [
 *      WorkerAbstractFactory::class => [
 *          'requestedName1' => [
 *              'queue' => 'queueServiceName',
 *              'callable' => 'callableServiceName',
 *              'writer' => 'writerServiceName'
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
class WorkerAbstractFactory implements AbstractFactoryInterface
{
    const KEY_QUEUE = 'queue';

    const KEY_CALLABLE = 'callable';

    const KEY_WRITER = 'writer';

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_QUEUE])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_QUEUE . "'");
        }

        if (!isset($serviceConfig[self::KEY_CALLABLE])) {
            throw new \InvalidArgumentException("Invalid option '" . self::KEY_CALLABLE . "'");
        }

        $queue = $container->get($serviceConfig[self::KEY_QUEUE]);
        $callable = $container->get($serviceConfig[self::KEY_CALLABLE]);
        $writer = isset($serviceConfig[self::KEY_CALLABLE]) ? $container->get($serviceConfig[self::KEY_WRITER]) : null;

        return new Worker($queue, $callable, $writer);
    }

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return !empty($container->get('config')[self::class][$requestedName] ?? []);
    }
}
