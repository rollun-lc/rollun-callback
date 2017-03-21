<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 04.03.17
 * Time: 11:03 AM
 */

namespace rollun\callback\Callback\Interruptor\Factory;


use rollun\callback\Callback\Callback;
use rollun\callback\Callback\CallbackInterface;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\callback\Callback\Interruptor\Process;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Interop\Container\ContainerInterface;

abstract class InterruptAbstractFactoryAbstract implements AbstractFactoryInterface
{
    const KEY = 'interrupt';

    const KEY_CLASS = 'class';

    const KEY_CALLBACK_SERVICE = 'callbackService';

    const DEFAULT_CLASS = InterruptorInterface::class;

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

}