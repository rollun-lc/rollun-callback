<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Factory;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use rollun\callback\Callback\SerializedCallback;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;

class SerializedCallbackAbstractFactory implements AbstractFactoryInterface
{
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
            $callable = $config[static::class][$requestedName];
        } else {
            $callable = $options['callable'];
        }

        if (is_array($callable) && !is_object(current($callable))) {
            array_unshift($callable, $container->get(array_shift($callable)));
        }

        if (is_string($callable) && $container->has($callable)){
            $callable = $container->get($callable);
        }

        return new SerializedCallback($callable);
    }
}
