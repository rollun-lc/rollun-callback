<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter\Factory;

use Interop\Container\ContainerInterface;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interrupter\Http;

class HttpClientAbstractFactory extends InterruptAbstractFactoryAbstract
{
    const KEY_URL = 'url';

    const KEY_OPTIONS = 'options';

    const DEFAULT_CLASS = Http::class;

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
        if (!isset($factoryConfig[static::KEY_URL])) {
            throw new CallbackException(static::KEY_URL . " not been set.");
        }
        $options = isset($factoryConfig[static::KEY_OPTIONS]) ? $factoryConfig[static::KEY_OPTIONS] : [];

        $url = $factoryConfig[static::KEY_URL];

        return new $class($url, $options);
    }
}
