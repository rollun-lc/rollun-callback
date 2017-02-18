<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 2:51 PM
 */

namespace rollun\callback\Callback\Interruptor\Factory;


use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Callback\Interruptor\Multiplexer;
use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Callback\Interruptor\Ticker;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class CronMultiplexerFactory extends AbstractMultiplexerFactory
{

    const KEY_CRON = 'cron';

    const KEY_SECOND_MULTIPLEXER_SERVICE = 'secondMultiplexerService';

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
        $factoryConfig = $config[static::KEY_MULTIPLEXER][static::KEY_CRON];

        $minMultiplexer = $this->getMultiplexer($container, $factoryConfig);

        if (isset($factoryConfig[static::KEY_SECOND_MULTIPLEXER_SERVICE]) &&
            $container->has($factoryConfig[static::KEY_SECOND_MULTIPLEXER_SERVICE])
        ) {
            $secMultiplexer = $container->get($factoryConfig[static::KEY_SECOND_MULTIPLEXER_SERVICE]);
            $secTicker = new Ticker(new Process($secMultiplexer));
            $minMultiplexer->addInterruptor($secTicker);
        }
        $minTicker = new Ticker(new Process($minMultiplexer), 1);
        return $minTicker;
    }
}