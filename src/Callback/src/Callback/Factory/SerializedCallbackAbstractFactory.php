<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Factory;

use Interop\Container\ContainerInterface;
use InvalidArgumentException;
use rollun\callback\Callback\SerializedCallback;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class SerializedCallbackAbstractFactory implements AbstractFactoryInterface
{
    const KEY_SERVICE_NAME = 'serviceName';

    const KEY_CALLBACK_METHOD = 'callbackMethod';

    const DEFAULT_CLASS = SerializedCallback::class;

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
     * @return mixed|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (empty($options)) {
            $config = $container->get('config');
            $serviceConfig = $config[static::class][$requestedName];
        } else {
            $serviceConfig = $options;
        }

        if (!isset($serviceConfig[static::KEY_SERVICE_NAME])) {
            throw new InvalidArgumentException("Invalid option '" . static::KEY_SERVICE_NAME . "'");
        }

        $service = $container->get($serviceConfig[static::KEY_SERVICE_NAME]);
        $method = $serviceConfig[static::KEY_CALLBACK_METHOD];

        if (!method_exists($service, $method)) {
            throw new InvalidArgumentException("Undefined method '$method' in class " . get_class($service));
        }

        return new SerializedCallback([$service, $serviceConfig[static::KEY_CALLBACK_METHOD]]);
    }
}
