<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\callback\Queues\Factory;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler;
use rollun\callback\Queues\Adapter\SqsAdapter;
use rollun\callback\Queues\DeadLetterQueue;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Create instance of SQSAdapter
 *
 * Config example:
 *
 * <code>
 *  [
 *      SqsAdapterAbstractFactory::class => [
 *          'requestedServiceName1' => [
 *              'priorityHandler' => 'priorityHandlerServiceName',
 *              'sqsClientConfig' => [
 *                  // ...
 *              ],
 *              'sqsAttributes' => [
 *                  'VisibilityTimeout' => 10,
 *                  // ...
 *              ]
 *          ],
 *          'requestedServiceName2' => [
 *
 *          ],
 *      ]
 *  ]
 * </code>
 *
 * Class SqsAdapterAbstractFactory
 * @package rollun\callback\Queues\Factory
 */
class SqsAdapterAbstractFactory implements AbstractFactoryInterface
{
    public const KEY_PRIORITY_HANDLER = 'priorityHandler';

    public const KEY_SQS_CLIENT_CONFIG = 'sqsClientConfig';

    public const KEY_SQS_ATTRIBUTES = 'sqsAttributes';

    public const KEY_MAX_RECEIVE_COUNT = 'maxReceiveCount';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        return !empty($container->get('config')[self::class][$requestedName]);
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return SQSAdapter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $options ?? $container->get('config')[self::class][$requestedName];

        if (isset($serviceConfig[self::KEY_PRIORITY_HANDLER])) {
            if (!$container->has($serviceConfig[self::KEY_PRIORITY_HANDLER])) {
                throw new InvalidArgumentException("Invalid option '" . self::KEY_PRIORITY_HANDLER . "'");
            }
            $priorityHandler = $container->get($serviceConfig[self::KEY_PRIORITY_HANDLER]);
        } else {
            $priorityHandler = $container->get(StandardPriorityHandler::class);
        }

        if (!isset($serviceConfig[self::KEY_SQS_CLIENT_CONFIG])) {
            throw new InvalidArgumentException("Invalid option '" . self::KEY_SQS_CLIENT_CONFIG . "'");
        }

        $attributes = $serviceConfig[self::KEY_SQS_ATTRIBUTES] ?? [];
        $maxMessageCount = $serviceConfig[self::KEY_MAX_RECEIVE_COUNT] ?? null;

        if ($maxMessageCount) {
            $deadLetterQueue = DeadLetterQueue::buildForQueueAdapter($requestedName, $serviceConfig[self::KEY_SQS_CLIENT_CONFIG]);
            $attributes['RedrivePolicy'] = json_encode([
                'deadLetterTargetArn' => $deadLetterQueue->getQueueArn(),
                'maxReceiveCount' => $maxMessageCount,
            ]);
        }

        return new SQSAdapter($serviceConfig[self::KEY_SQS_CLIENT_CONFIG], $priorityHandler, $attributes);
    }
}
