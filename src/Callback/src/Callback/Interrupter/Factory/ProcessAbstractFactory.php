<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter\Factory;

use Interop\Container\ContainerInterface;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\Callback\CallbackException;

class ProcessAbstractFactory extends InterruptAbstractFactoryAbstract
{
    const DEFAULT_CLASS = Process::class;

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $factoryConfig = $config[static::KEY][$requestedName];
        $class = $factoryConfig[static::KEY_CLASS];
        $callback = $factoryConfig[static::KEY_CALLBACK_SERVICE];
        if (!$container->has($callback)) {
            throw new CallbackException("Service with name '$callback' - not found.");
        }
        $callback = $container->get($callback);

        return new $class(new SerializedCallback($callback));
    }
}
