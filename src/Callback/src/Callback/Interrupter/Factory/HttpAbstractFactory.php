<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter\Factory;

use Psr\Container\ContainerInterface;
use rollun\callback\Callback\Http;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\Callback\CallbackException;

class HttpAbstractFactory extends InterruptAbstractFactoryAbstract
{
    public const KEY_URL = 'url';

    public const KEY_OPTIONS = 'options';

    public const DEFAULT_CLASS = Http::class;

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
        if (!isset($factoryConfig[static::KEY_URL])) {
            throw new CallbackException(static::KEY_URL . " not been set.");
        }
        $url = $factoryConfig[static::KEY_URL];
        if (!$container->has($callback)) {
            throw new CallbackException("Service with name '$callback' hasn't found.");
        }
        $callback = $container->get($callback);

        $options = $factoryConfig[static::KEY_OPTIONS] ?? [];

        return new $class(new SerializedCallback($callback), $url, $options);
    }
}
