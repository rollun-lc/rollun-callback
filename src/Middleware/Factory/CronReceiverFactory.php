<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20.01.17
 * Time: 14:03
 */

namespace rollun\callback\Middleware\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Middleware\CronReceiver;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class CronReceiverFactory implements FactoryInterface
{

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
        $config = $container->get('config')['cron'];
        $secMultiplexor = $container->get($config[CronReceiver::KEY_SEC_MULTIPLEXER]);
        $minMultiplexor = $container->get($config[CronReceiver::KEY_MIN_MULTIPLEXER]);
        return new CronReceiver($secMultiplexor, $minMultiplexor);
    }
}
