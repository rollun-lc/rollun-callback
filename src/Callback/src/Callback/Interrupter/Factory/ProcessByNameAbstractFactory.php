<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter\Factory;

use Psr\Container\ContainerInterface;
use rollun\callback\Callback\Interrupter\ProcessByName;
use rollun\callback\ConfigProvider;

class ProcessByNameAbstractFactory extends InterruptAbstractFactoryAbstract
{
    public const DEFAULT_CLASS = ProcessByName::class;

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
