<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 18.02.17
 * Time: 2:20 PM
 */

namespace rollun\callback\Callback\Interruptor\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use rollun\callback\Callback\Interruptor\Multiplexer;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class MultiplexerAbstractFactory extends AbstractInterruptorAbstractFactory
{
    const KEY_INTERRUPTERS_SERVICE = 'interrupters';

    const DEFAULT_CLASS = Multiplexer::class;

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

        $interrupters = [];
        if (isset($factoryConfig[static::KEY_INTERRUPTERS_SERVICE])) {
            $interruptersService = $factoryConfig[static::KEY_INTERRUPTERS_SERVICE];
            foreach ($interruptersService as $interrupterService) {
                if ($container->has($interrupterService)) {
                    $interrupters[] = $container->get($interrupterService);
                }
            }
        }

        $class = $factoryConfig[static::KEY_CLASS];
        $multiplexer = new $class($interrupters);

        return $multiplexer;
    }
}