<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.03.17
 * Time: 17:01
 */

namespace rollun\callback\Callback\Interruptor\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Callback\Interruptor\InterruptorInterface;
use rollun\callback\Callback\Interruptor\Ticker;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class TickerAbstractFactory extends InterruptorAbstractFactoryAbstract
{

    const KEY_CALLBACK = 'callback';

    const DEFAULT_CLASS = Ticker::class;

    const KEY_TICKS_COUNT = 'ticks_count';

    const KEY_TICK_DURATION = 'tick_duration';

    /**
     * @deprecate KEY_WRAPPER_CLASS
     */
    const KEY_WRAPPER_CLASS = 'wrapper_class';

    const KEY_DELAY_MC = 'delay_MC';

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $factoryConfig = $config[static::KEY][$requestedName];
        $ticksCount = isset($factoryConfig[static::KEY_TICKS_COUNT]) ? $factoryConfig[static::KEY_TICKS_COUNT] : 60;
        $tickDuration = isset($factoryConfig[static::KEY_TICK_DURATION]) ? $factoryConfig[static::KEY_TICK_DURATION] : 1;
        $delayMC = isset($factoryConfig[static::KEY_DELAY_MC]) ? $factoryConfig[static::KEY_DELAY_MC] : 0;
        if(!isset($factoryConfig[static::KEY_CALLBACK])) {
            throw new ServiceNotCreatedException('Callback not set.');
        }
        if(!$container->has($factoryConfig[static::KEY_CALLBACK])) {
            throw new ServiceNotFoundException($factoryConfig[static::KEY_CALLBACK] . ' service not found.');
        }
        $tickerCallback = $container->get($factoryConfig[static::KEY_CALLBACK]);
        $class = $factoryConfig[static::KEY_CLASS];

        return new $class($tickerCallback, $ticksCount, $tickDuration, $delayMC);
    }
}
