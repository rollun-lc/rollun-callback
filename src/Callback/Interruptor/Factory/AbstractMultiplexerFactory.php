<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 2:56 PM
 */

namespace rollun\callback\Callback\Interruptor\Factory;


use Interop\Container\ContainerInterface;
use rollun\callback\Callback\Interruptor\Multiplexer;
use Zend\ServiceManager\Factory\FactoryInterface;

abstract class AbstractMultiplexerFactory implements FactoryInterface
{
    const KEY_MULTIPLEXER = 'multiplexer';

    const KEY_INTERRUPTERS_SERVICE = 'interrupters';

    const KEY_CLASS = 'class';

    const DEFAULT_CLASS = Multiplexer::class;


    /**
     * @param ContainerInterface $container
     * @param array $config
     * @return Multiplexer
     */
    protected function getMultiplexer(ContainerInterface $container, array $config)
    {
        $interrupters = [];
        if (isset($config[static::KEY_INTERRUPTERS_SERVICE])) {
            $interruptersService = $config[static::KEY_INTERRUPTERS_SERVICE];
            foreach ($interruptersService as $interrupterService) {
                if ($container->has($interrupterService)) {
                    $interrupters[] = $container->get($interrupterService);
                }
            }
        }

        $class = (isset($config[static::KEY_CLASS]) &&
        is_a($config[static::KEY_CLASS], static::DEFAULT_CLASS, true)) ?
            $config[static::KEY_CLASS] : (static::DEFAULT_CLASS);
        $multiplexer = new $class($interrupters);

        return $multiplexer;
    }
}