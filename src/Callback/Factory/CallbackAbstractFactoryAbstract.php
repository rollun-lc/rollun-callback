<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 04.03.17
 * Time: 11:03 AM
 */

namespace rollun\callback\Callback\Factory;


use rollun\callback\Callback\Callback;
use rollun\callback\Callback\CallbackInterface;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\callback\Callback\Interruptor\Process;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Interop\Container\ContainerInterface;

abstract class CallbackAbstractFactoryAbstract implements AbstractFactoryInterface
{
    const KEY = 'callback';

    const KEY_CLASS = 'class';

    const DEFAULT_CLASS = CallbackInterface::class;

    const DEFAULT_WRAPPED_CLASS = Process::class;

    const WRAPPED_CLASS = Process::class;

    /**
     * Can the factory create an instance for the service?
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');
        return (isset($config[static::KEY][$requestedName]) &&
            is_a($config[static::KEY][$requestedName][static::KEY_CLASS], static::DEFAULT_CLASS, true));
    }

    public function wrappedCallback(callable $callback, array $config)
    {
        if (isset($config[static::WRAPPED_CLASS])) {
            $class = is_a($config[static::WRAPPED_CLASS], InterruptorInterface::class, true) ?
                $config[static::WRAPPED_CLASS] : static::DEFAULT_WRAPPED_CLASS;
            return new $class(new Callback($callback));
        }
        return new Callback($callback);
    }
}