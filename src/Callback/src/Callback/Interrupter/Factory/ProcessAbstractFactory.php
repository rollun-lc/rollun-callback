<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter\Factory;

use Psr\Container\ContainerInterface;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\ConfigProvider;

class ProcessAbstractFactory extends InterruptAbstractFactoryAbstract
{
    public const DEFAULT_CLASS = Process::class;

    public const KEY_MAX_EXECUTE_TIME = 'maxExecuteTime';

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed|object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $factoryConfig = $options ?? $container->get('config')[static::KEY][$requestedName];

        $class = $factoryConfig[static::KEY_CLASS];
        $callback = $factoryConfig[static::KEY_CALLBACK_SERVICE];

        if (is_string($callback)) {
            if (!$container->has($callback)) {
                throw new CallbackException("Service with name '$callback' - not found.");
            }
            $callback = $container->get($callback);
        }

        $maxExecuteTime = $factoryConfig[self::KEY_MAX_EXECUTE_TIME] ?? null;
        $pidKiller = null;

        if ($maxExecuteTime && $container->has(ConfigProvider::PID_KILLER_SERVICE)) {
            $pidKiller = $container->get(ConfigProvider::PID_KILLER_SERVICE);
        } else {
            $maxExecuteTime = null;
        }

        return new $class($callback, $pidKiller, $maxExecuteTime);
    }
}
