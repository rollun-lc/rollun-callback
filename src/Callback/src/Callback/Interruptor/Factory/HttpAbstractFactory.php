<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 21.03.17
 * Time: 18:40
 */

namespace rollun\callback\Callback\Interruptor\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Callback\Callback;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interruptor\Http;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class HttpAbstractFactory extends InterruptAbstractFactoryAbstract
{
    const KEY_URL = 'url';

    const KEY_OPTIONS = 'options';

    const DEFAULT_CLASS = Http::class;

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
        $callback = $factoryConfig[static::KEY_CALLBACK_SERVICE];
        if(!isset($factoryConfig[static::KEY_URL])){
            throw new CallbackException(static::KEY_URL . " not been set.");
        }
        $url = $factoryConfig[static::KEY_URL];
        if(!$container->has($callback)) {
            throw new CallbackException("Service with name '$callback' hasn't found.");
        }
        $callback = $container->get($callback);

        $options = isset($factoryConfig[static::KEY_OPTIONS]) ? $factoryConfig[static::KEY_OPTIONS] : [];

        return new $class(new Callback($callback), $url, $options);
    }
}
