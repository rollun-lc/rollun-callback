<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\callback\Queues\Factory;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use ReputationVIP\QueueClient\Adapter\MemoryAdapter;
use ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler;
use rollun\callback\Queues\Adapter\SqsAdapter;
use rollun\callback\Queues\DeadLetterQueue;
use rollun\callback\Queues\QueueClient;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

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
    const KEY_CLASS = 'class';

    const KEY_DEFAULT_CLASS = QueueClient::class;

    const KEY_DELAY = 'delay';

    const KEY_NAME = 'name';

    const KEY_ADAPTER = 'adapter';

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
        $serviceConfig = $container->get('config')[self::class][$requestedName];

        if (!isset($serviceConfig[self::KEY_ADAPTER])) {
            throw new InvalidArgumentException("Invalid option '" . self::KEY_ADAPTER . "'");
        }

        if (!isset($serviceConfig[self::KEY_NAME])) {
            throw new InvalidArgumentException("Invalid option '" . self::KEY_NAME . "'");
        }


        if (is_array($serviceConfig[self::KEY_ADAPTER])) {
            $adapter = $this->createSqsAdapter($container, $requestedName, $serviceConfig[self::KEY_ADAPTER]);
        } elseif (is_string($serviceConfig[self::KEY_ADAPTER]) && $container->has($serviceConfig[self::KEY_ADAPTER])) {
            $adapter = $container->get($serviceConfig[self::KEY_ADAPTER]);
        } else {
            throw new InvalidArgumentException("Invalid option '" . self::KEY_ADAPTER . "'");
        }

        $delay = $serviceConfig[self::KEY_DELAY] ?? 0;
        $queueName = $serviceConfig[self::KEY_NAME];
        $class = $serviceConfig[self::KEY_CLASS] ?? self::KEY_DEFAULT_CLASS;

        return new $class($adapter, $queueName, $delay);
    }

    public static function createSimpleQueueClient(): QueueClient
    {
        return new QueueClient(new MemoryAdapter(), sha1(openssl_random_pseudo_bytes(1024)));
    }

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param $serviceAdapterConfig
     * @return SqsAdapter
     */
    private function createSqsAdapter(ContainerInterface $container, $requestedName, $serviceAdapterConfig): SqsAdapter
    {
        if (isset($serviceAdapterConfig[SqsAdapterAbstractFactory::KEY_PRIORITY_HANDLER])) {
            if (!$container->has($serviceAdapterConfig[SqsAdapterAbstractFactory::KEY_PRIORITY_HANDLER])) {
                throw new InvalidArgumentException("Invalid option '" . SqsAdapterAbstractFactory::KEY_PRIORITY_HANDLER . "'");
            }
            $priorityHandler = $container->get($serviceAdapterConfig[SqsAdapterAbstractFactory::KEY_PRIORITY_HANDLER]);
        } else {
            $priorityHandler = $container->get(StandardPriorityHandler::class);
        }

        if (!isset($serviceAdapterConfig[SqsAdapterAbstractFactory::KEY_SQS_CLIENT_CONFIG])) {
            throw new InvalidArgumentException("Invalid option '" . SqsAdapterAbstractFactory::KEY_SQS_CLIENT_CONFIG . "'");
        }

        $attributes = $serviceAdapterConfig[SqsAdapterAbstractFactory::KEY_SQS_ATTRIBUTES] ?? [];
        $maxMessageCount = $serviceAdapterConfig[SqsAdapterAbstractFactory::KEY_MAX_RECEIVE_COUNT] ?? null;

        if ($maxMessageCount) {
            $deadLetterQueue = new DeadLetterQueue($requestedName, $serviceAdapterConfig[SqsAdapterAbstractFactory::KEY_SQS_CLIENT_CONFIG]);
            $attributes['RedrivePolicy'] = json_encode([
                'deadLetterTargetArn' => $deadLetterQueue->getQueueArn(),
                'maxReceiveCount' => $maxMessageCount,
            ]);
        }
        $adapter = new SQSAdapter($serviceAdapterConfig[SqsAdapterAbstractFactory::KEY_SQS_CLIENT_CONFIG], $priorityHandler, $attributes);
        return $adapter;
    }
}
