<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\callback\Queues\Factory;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use ReputationVIP\QueueClient\Adapter\MemoryAdapter;
use rollun\callback\Queues\Adapter\DbAdapter;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\callback\Queues\Adapter\SqsAdapter;
use rollun\callback\Queues\QueueClient;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Create instance of QueueClient
 *
 * Config example:
 *
 * <code>
 *  [
 *      QueueClientAbstractFactory::class => [
 *          'requestedServiceName1' => [
 *              'delay' => 30, // in seconds,
 *              'name' => 'testQueue',
 *              'adapter' => SqsAdapter,
 *          ],
 *          'requestedServiceName2' => [
 *
 *          ],
 *      ]
 *  ]
 * </code>
 *
 * Class QueueClientAbstractFactory
 * @package rollun\callback\Queues\Factory
 */
class QueueClientAbstractFactory implements AbstractFactoryInterface
{
    public const KEY_CLASS = 'class';

    public const KEY_DEFAULT_CLASS = QueueClient::class;

    public const DEFAULT_ADAPTER = SqsAdapter::class;

    public const KEY_DELAY = 'delay';

    public const KEY_NAME = 'name';

    public const KEY_ADAPTER = 'adapter';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $serviceConfig = $container->get('config')[self::class][$requestedName] ?? [];

        if (empty($serviceConfig)) {
            return false;
        }

        if (isset($serviceConfig[self::KEY_CLASS])
            && !is_a($serviceConfig[self::KEY_CLASS], self::KEY_DEFAULT_CLASS, true)) {
            return false;
        }

        return true;
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return QueueClient
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $options ?? $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_ADAPTER])) {
            throw new InvalidArgumentException("Invalid option '" . self::KEY_ADAPTER . "'");
        }

        if (!isset($serviceConfig[self::KEY_NAME])) {
            throw new InvalidArgumentException("Invalid option '" . self::KEY_NAME . "'");
        }


        if (is_array($serviceConfig[self::KEY_ADAPTER])) {
            $adapter = self::buildAdapter($container, $requestedName, $serviceConfig[self::KEY_ADAPTER]);
        } elseif (is_string($serviceConfig[self::KEY_ADAPTER]) && $container->has($serviceConfig[self::KEY_ADAPTER])) {
            $adapter = $container->get($serviceConfig[self::KEY_ADAPTER]);
        } elseif ($serviceConfig[self::KEY_ADAPTER] instanceof AdapterInterface) {
            $adapter = $serviceConfig[self::KEY_ADAPTER];
        } else {
            throw new InvalidArgumentException("Invalid option '" . self::KEY_ADAPTER . "'");
        }

        $delay = $serviceConfig[self::KEY_DELAY] ?? 0;
        $queueName = $serviceConfig[self::KEY_NAME];
        $class = $serviceConfig[self::KEY_CLASS] ?? self::KEY_DEFAULT_CLASS;

        return new $class($adapter, $queueName, $delay);
    }

    /**
     * @return QueueClient
     * @throws \Exception
     */
    public static function createSimpleQueueClient(): QueueClient
    {
        return new QueueClient(new MemoryAdapter(), sha1(random_bytes(1024)));
    }

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return SqsAdapter
     */
    private static function buildAdapter(ContainerInterface $container, $requestedName, array $options = null): AdapterInterface
    {
        $adapterName = sprintf('%s_Adapter', $requestedName);
        $adapterType = $options[self::KEY_CLASS] ?? self::DEFAULT_ADAPTER;
        switch ($adapterType) {
            case SqsAdapter::class:
                return (new SqsAdapterAbstractFactory())($container, $adapterName, $options);
            case FileAdapter::class:
                return (new FileAdapterAbstractFactory())($container, $adapterName, $options);
            case DbAdapter::class:
                return (new DbAdapterAbstractFactory())($container, $adapterName, $options);
            default:
                throw new \Laminas\ServiceManager\Exception\InvalidArgumentException(sprintf('Unknown adapter type %s for queue %s', $adapterType, $requestedName));
        }
    }
}
