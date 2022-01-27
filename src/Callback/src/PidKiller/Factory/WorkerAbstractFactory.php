<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller\Factory;

use Interop\Container\ContainerInterface;
use Jaeger\Tracer\Tracer;
use Psr\Log\LoggerInterface;
use rollun\callback\PidKiller\Worker;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Interop\Container\Exception\ContainerException;

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
    public const KEY_QUEUE = 'queue';

    public const KEY_CALLABLE = 'callable';

    public const KEY_WRITER = 'writer';

    public const KEY_INFO = 'info';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object|Worker
     * @throws ServiceNotCreatedException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        try {

            $serviceConfig = $options ?? $container->get('config')[self::class][$requestedName];

            if (!isset($serviceConfig[self::KEY_QUEUE])) {
                throw new \InvalidArgumentException("Invalid option '" . self::KEY_QUEUE . "'");
            }

            if (!isset($serviceConfig[self::KEY_CALLABLE])) {
                throw new \InvalidArgumentException("Invalid option '" . self::KEY_CALLABLE . "'");
            }


            $queue = is_string($serviceConfig[self::KEY_QUEUE]) ? $container->get($serviceConfig[self::KEY_QUEUE]) : $serviceConfig[self::KEY_QUEUE];
            $callable = is_string($serviceConfig[self::KEY_CALLABLE]) ? $container->get($serviceConfig[self::KEY_CALLABLE]) : $serviceConfig[self::KEY_CALLABLE];
            $writer = isset($serviceConfig[self::KEY_WRITER]) ? $container->get($serviceConfig[self::KEY_WRITER]) : null;
            $info = $serviceConfig[self::KEY_INFO] ?? '';
            $logger = $container->get(LoggerInterface::class);
            $tracer = $container->get(Tracer::class);
            return new Worker($queue, $callable, $writer, $logger, $tracer, $info);
        } catch (\Throwable $throwable) {
            throw new ServiceNotCreatedException(sprintf('Can\'t service service %s. Reason: %s', $requestedName, $throwable->getMessage()), $throwable->getCode(), $throwable);
        }
    }

    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return !empty($container->get('config')[self::class][$requestedName] ?? []);
    }
}
