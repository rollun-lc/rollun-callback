<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 19.04.17
 * Time: 10:42
 */

namespace rollun\callback\Queues\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Queues\Queue;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class QueueAbstractFactory implements AbstractFactoryInterface
{
    const KEY = 'keyQueues';

    const KEY_DELAY = 'keyDelay';

    //const KEY_QUEUE_NAME = 'keyQueueName';

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        return isset($config[static::KEY][$requestedName]);
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (empty($options)) {
            $config = $container->get('config');
            $serviceConfig = $config[static::KEY][$requestedName];
        } else {
            $serviceConfig = $options;
        }
        if(isset($serviceConfig[static::KEY_DELAY])){
            $queue = new Queue($requestedName, $serviceConfig[static::KEY_DELAY]);
        } else {
            $queue = new Queue($requestedName);
        }
        return $queue;
    }
}
