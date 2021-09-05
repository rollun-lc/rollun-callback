<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types = 1);

namespace rollun\callback\Queues\Factory;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use rollun\callback\Queues\Adapter\DbAdapter;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

/**
 * Create instance of DbAdapter
 *
 * Config example:
 *
 * <code>
 *  [
 *      DbAdapterAbstractFactory::class => [
 *          'requestedServiceName1' => [
 *              'priorityHandler' => 'priorityHandlerServiceName',
 *              'timeInflight' => 0,
 *              'maxReceiveCount' => 0,
 *          ],
 *          'requestedServiceName2' => [
 *
 *          ],
 *      ]
 *  ]
 * </code>
 *
 * Class DbAdapterAbstractFactory
 * @package rollun\callback\Queues\Factory
 */
class DbAdapterAbstractFactory implements AbstractFactoryInterface
{
    const KEY_PRIORITY_HANDLER = 'priorityHandler';

    const KEY_TIME_IN_FLIGHT = 'timeInflight';

    public const KEY_DB_ADAPTER = 'adapter';

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
     * @return DbAdapter
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @noinspection DuplicatedCode */
        $serviceConfig = $options ?? $container->get('config')[self::class][$requestedName];

        if (isset($serviceConfig[self::KEY_PRIORITY_HANDLER])) {
            if (!$container->has($serviceConfig[self::KEY_PRIORITY_HANDLER])) {
                throw new InvalidArgumentException("Invalid option '" . self::KEY_PRIORITY_HANDLER . "'");
            } else {
                $priorityHandler = $container->get($serviceConfig[self::KEY_PRIORITY_HANDLER]);
            }
        } else {
            $priorityHandler = null;
        }

        $dbAdapterName = $serviceConfig[self::KEY_DB_ADAPTER] ?? 'db';
        $db = $container->get($dbAdapterName);
        $timeInFlight = $serviceConfig[self::KEY_TIME_IN_FLIGHT] ?? 0;
        $maxMessageCount = $serviceConfig[self::KEY_MAX_RECEIVE_COUNT] ?? 0;

        return new DbAdapter($db, $timeInFlight, $maxMessageCount, $priorityHandler, $dbAdapterName);
    }
}
