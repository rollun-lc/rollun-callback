<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 19.04.17
 * Time: 11:14
 */

namespace rollun\callback\Queues\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Queues\Extractor;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class ExtractorAbstractFactory implements AbstractFactoryInterface
{

    const KEY = 'keyQueueExtractor';

    const KEY_QUEUE_SERVICE_NAME = 'keyQueueServiceName';

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
        $queue = $container->get($serviceConfig[static::KEY_QUEUE_SERVICE_NAME]);
        $extractor = new Extractor($queue);
        return $extractor;
    }
}
