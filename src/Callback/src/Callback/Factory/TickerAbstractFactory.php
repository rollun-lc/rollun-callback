<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Factory;

use Psr\Container\ContainerInterface;
use rollun\callback\Callback\Ticker;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class TickerAbstractFactory extends CallbackAbstractFactoryAbstract
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
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $factoryConfig = $config[static::KEY][$requestedName];
        $ticksCount = $factoryConfig[static::KEY_TICKS_COUNT] ?? 60;
        $tickDuration = $factoryConfig[static::KEY_TICK_DURATION] ?? 1;
        $delayMC = $factoryConfig[static::KEY_DELAY_MC] ?? 0;
        if (!isset($factoryConfig[static::KEY_CALLBACK])) {
            throw new ServiceNotCreatedException('Callback not set.');
        }
        if (!$container->has($factoryConfig[static::KEY_CALLBACK])) {
            throw new ServiceNotFoundException($factoryConfig[static::KEY_CALLBACK] . ' service not found.');
        }
        $tickerCallback = $container->get($factoryConfig[static::KEY_CALLBACK]);
        $class = $factoryConfig[static::KEY_CLASS];

        $ticker = new $class($tickerCallback, $ticksCount, $tickDuration, $delayMC);

        return $ticker;
    }
}
