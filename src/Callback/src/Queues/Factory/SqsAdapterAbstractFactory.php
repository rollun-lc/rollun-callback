<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types = 1);

namespace rollun\callback\Queues\Factory;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler;
use rollun\callback\Queues\Adapter\SqsAdapter;
use rollun\callback\Queues\DeadLetterQueue;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

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
    const KEY_PRIORITY_HANDLER = 'priorityHandler';

    const KEY_SQS_CLIENT_CONFIG = 'sqsClientConfig';

    const KEY_SQS_ATTRIBUTES = 'sqsAttributes';

    const KEY_MAX_RECEIVE_COUNT = 'maxReceiveCount';

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
        $serviceConfig = $container->get('config')[self::class][$requestedName];

        if (isset($serviceConfig[self::KEY_PRIORITY_HANDLER])) {
            if (!$container->has($serviceConfig[self::KEY_PRIORITY_HANDLER])) {
                throw new InvalidArgumentException("Invalid option '" . self::KEY_PRIORITY_HANDLER . "'");
            } else {
                $priorityHandler = $container->get($serviceConfig[self::KEY_PRIORITY_HANDLER]);
            }
        } else {
            $priorityHandler = $container->get(StandardPriorityHandler::class);
        }

        if (!isset($serviceConfig[self::KEY_SQS_CLIENT_CONFIG])) {
            throw new InvalidArgumentException("Invalid option '" . self::KEY_SQS_CLIENT_CONFIG . "'");
        }

        $attributes = $serviceConfig[self::KEY_SQS_ATTRIBUTES] ?? [];
        $maxMessageCount = $serviceConfig[self::KEY_MAX_RECEIVE_COUNT] ?? null;

        if ($maxMessageCount) {
            /** @var DeadLetterQueue $deadLetterQueue */
            $deadLetterQueue = $container->get(DeadLetterQueue::class);
            $attributes['RedrivePolicy'] = json_encode([
                'deadLetterTargetArn' => $deadLetterQueue->getQueueArn(),
                'maxReceiveCount' => $maxMessageCount,
            ]);
        }

        return new SQSAdapter($serviceConfig[self::KEY_SQS_CLIENT_CONFIG], $priorityHandler, $attributes);
    }
}
