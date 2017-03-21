<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 2:20 PM
 */

namespace rollun\callback\Callback\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Callback\Callback;
use rollun\callback\Callback\CallbackInterface;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Callback\Interruptor\Process;
use rollun\logger\Logger;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class MultiplexerAbstractFactory extends CallbackAbstractFactoryAbstract
{
    const KEY_INTERRUPTERS_SERVICE = 'interrupters';

    const DEFAULT_CLASS = Multiplexer::class;

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return CallbackInterface|InterruptorInterface
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $logger = new Logger();

        $config = $container->get('config');
        $factoryConfig = $config[static::KEY][$requestedName];

        $callbacks = [];
        if (isset($factoryConfig[static::KEY_INTERRUPTERS_SERVICE])) {
            $callbackService = $factoryConfig[static::KEY_INTERRUPTERS_SERVICE];
            foreach ($callbackService as $callback) {
                if (is_callable($callback)) {
                    $callbacks[] = $callback instanceof Callback ? $callback : new Callback($callback);
                } else if ($container->has($callback)) {
                    $callbacks[] = ($container->get($callback));
                } else {
                    $logger->alert("callback with name $callback not found in container.");
                }
            }
        }

        $class = $factoryConfig[static::KEY_CLASS];
        $multiplexer = new $class($callbacks);

        return $this->wrappedCallback($multiplexer, $factoryConfig);
    }
}