<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.10.17
 * Time: 18:53
 */

namespace rollun\callback\Queues\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Queues\AbstractQueue;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class QueuesAbstractFactory implements AbstractFactoryInterface
{

    const KEY = QueuesAbstractFactory::class;

    const KEY_CLASS = "class";

    const DEFAULT_CLASS = AbstractQueue::class;

    const KEY_QUEUE_NAME = "keyQueueName";

    const KEY_DELAY = "keyDelay";

    const KEY_PRIORITY_HANDLER_CLASS = "keyPriorityHandlerClass";

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get("config");
        return (
            isset($config[static::KEY][$requestedName]) &&
            isset($config[static::KEY][$requestedName][static::KEY_CLASS]) &&
            is_a($config[static::KEY][$requestedName][static::KEY_CLASS], static::DEFAULT_CLASS, true)
        );
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $serviceConfig = $this->getServiceConfig($container, $requestedName, $options);

        $class = $serviceConfig[static::KEY_CLASS];
        $params = [];
        $params[] = $serviceConfig[static::KEY_QUEUE_NAME];
        if(isset($serviceConfig[static::KEY_DELAY])) {
            $params[] = $serviceConfig[static::KEY_DELAY];
        } else {
            $params[] = 0;
        }
        
        if(isset($serviceConfig[static::KEY_PRIORITY_HANDLER_CLASS])) {
            $params[] = $serviceConfig[static::KEY_PRIORITY_HANDLER_CLASS];
        }
        return new $class(...$params);
    }

    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getServiceConfig(ContainerInterface $container, $requestedName, array $options = null)
    {
        $options = isset($options) ? $options : [];
        $config = $container->get("config");
        $serviceConfig = array_merge($options, $config[static::KEY][$requestedName]);
        return $serviceConfig;
    }
}
