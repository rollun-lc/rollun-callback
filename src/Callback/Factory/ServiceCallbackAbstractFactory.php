<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 05.04.17
 * Time: 20:55
 */

namespace rollun\callback\Callback\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Callback\Callback;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class ServiceCallbackAbstractFactory extends CallbackAbstractFactoryAbstract
{
    const KEY_SERVICE_NAME = 'serviceName';

    const KEY_CALLBACK_METHOD = 'callbackMethod';

    const DEFAULT_CLASS = Callback::class;

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

        $class = $serviceConfig[static::KEY_CLASS];

        //todo: add check param in array and method has
        $service = $container->get($serviceConfig[static::KEY_SERVICE_NAME]);
        return new $class([$service, $serviceConfig[static::KEY_CALLBACK_METHOD]]);
    }
}

