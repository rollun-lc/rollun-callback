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
use rollun\callback\Callback\CallbackInterface;
use rollun\callback\Callback\Interruptor\ServiceQueue as ServiceQueueInterruptor;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class ServiceQueueAbstractFactory extends InterruptAbstractFactoryAbstract
{
    const KEY_QUEUE_SERVICE = 'queue';

    const KEY_MESSAGE_PRIORITY = 'priority';

    const DEFAULT_CLASS = ServiceQueueInterruptor::class;

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

        $queue = $factoryConfig[static::KEY_QUEUE_SERVICE];
        if(!$container->has($queue)) {
            throw new CallbackException("Service with name '$queue' - not found.");
        }
        $queue = $container->get($queue);
		$priority = $factoryConfig[static::KEY_MESSAGE_PRIORITY] ?? null;
        return new $class($queue, $priority);
    }
}
