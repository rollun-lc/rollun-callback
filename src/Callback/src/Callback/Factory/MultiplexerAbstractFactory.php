<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Factory;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\Callback\Multiplexer;

class MultiplexerAbstractFactory extends CallbackAbstractFactoryAbstract
{
    public const KEY_CALLBACKS_SERVICES = 'interrupters';

    public const DEFAULT_CLASS = Multiplexer::class;

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
                // A service name must be resolved through the container even when it happens to
                // be callable on its own, e.g. when it collides with a global function name.
                if (is_string($callback) && $container->has($callback)) {
                    $callbacks[$name] = [
                        Multiplexer\CallbackObject::CALLBACK_KEY => $container->get($callback),
                        Multiplexer\CallbackObject::NAME_KEY => $callback,
                    ];
                } elseif ($callback instanceof Multiplexer\CallbackObject) {
                    // checked before is_callable(), which would swallow it and drop its name
                    $callbacks[$name] = $callback;
                } elseif (is_callable($callback)) {
                    $callbacks[$name] = $callback instanceof SerializedCallback ? $callback : new SerializedCallback($callback);
                } elseif (is_array($callback)) {
                    $callbacks[$name] = $callback;
                } else {
                    $described = is_string($callback) ? $callback : get_debug_type($callback);
                    $logger->alert("Callback with name $described not found in container.");
                }
            }
        }

        $logger = $container->get(LoggerInterface::class);
        $class = $factoryConfig[static::KEY_CLASS];
        $name = is_string($requestedName) ? $requestedName : null;
        $multiplexer = new $class($logger, $callbacks, $name);

        return $multiplexer;
    }
}
