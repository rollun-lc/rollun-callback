<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter\Factory;

use Interop\Container\ContainerInterface;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interrupter\QueueFiller;

class QueueMessageFillerAbstractFactory extends InterruptAbstractFactoryAbstract
{
    const KEY_QUEUE_SERVICE = 'queue';

    const DEFAULT_CLASS = QueueFiller::class;

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
        $class = $factoryConfig[static::KEY_CLASS];

        $queue = $factoryConfig[static::KEY_QUEUE_SERVICE];
        if (!$container->has($queue)) {
            throw new CallbackException("Service with name '$queue' - not found.");
        }
        $queue = $container->get($queue);

        return new $class($queue);
    }
}
