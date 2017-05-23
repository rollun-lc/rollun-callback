<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 02.05.17
 * Time: 18:48
 */

namespace rollun\callback\Callback\Interruptor\Factory;

use Interop\Container\ContainerInterface;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interruptor\HttpClient;

class HttpClientAbstractFactory extends InterruptAbstractFactoryAbstract
{
    const KEY_URL = 'url';
    const KEY_OPTIONS = 'options';

    const DEFAULT_CLASS = HttpClient::class;

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws CallbackException
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $factoryConfig = $config[static::KEY][$requestedName];
        $class = $factoryConfig[static::KEY_CLASS];
        if(!isset($factoryConfig[static::KEY_URL])){
            throw new CallbackException(static::KEY_URL . " not been set.");
        }
        $options = isset($factoryConfig[static::KEY_OPTIONS]) ? $factoryConfig[static::KEY_OPTIONS] : [];

        $url = $factoryConfig[static::KEY_URL];
        return new $class($url, $options);
    }
}
