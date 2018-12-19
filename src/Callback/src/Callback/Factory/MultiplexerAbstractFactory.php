<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Factory;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\Callback\Multiplexer;

class MultiplexerAbstractFactory extends CallbackAbstractFactoryAbstract
{
    const KEY_CALLBACKS_SERVICES = 'interrupters';

    const DEFAULT_CLASS = Multiplexer::class;

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {

        $config = $container->get('config');
        $logger = $container->get(LoggerInterface::class);
        $factoryConfig = $config[static::KEY][$requestedName];

        $callbacks = [];
        if (isset($factoryConfig[static::KEY_CALLBACKS_SERVICES])) {
            $callbackService = $factoryConfig[static::KEY_CALLBACKS_SERVICES];
            foreach ($callbackService as $name => $callback) {
                if (is_callable($callback)) {
                    $callbacks[$name] = $callback instanceof SerializedCallback ? $callback : new SerializedCallback($callback);
                } else {
                    if ($container->has($callback)) {
                        $callbacks[$name] = ($container->get($callback));
                    } else {
                        $logger->alert("Callback with name $callback not found in container.");
                    }
                }
            }
        }

        $logger = $container->get(LoggerInterface::class);
        $class = $factoryConfig[static::KEY_CLASS];
        $multiplexer = new $class($logger, $callbacks);

        return $multiplexer;
    }
}
