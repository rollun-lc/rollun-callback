<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Factory;

use Psr\Container\ContainerInterface;
use rollun\callback\Callback\CronExpression;
use rollun\callback\Callback\Factory\CallbackAbstractFactoryAbstract;
use rollun\callback\Callback\CallbackException;

class CronExpressionAbstractFactory extends CallbackAbstractFactoryAbstract
{
    public const KEY_EXPRESSION = 'expression';

    public const KEY_CALLBACK_SERVICE = 'callback';

    public const DEFAULT_CLASS = CronExpression::class;

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
        if (!isset($factoryConfig[static::KEY_EXPRESSION])) {
            throw new CallbackException(static::KEY_EXPRESSION . " not been set.");
        }
        $expression = $factoryConfig[static::KEY_EXPRESSION];
        if (!$container->has($callback)) {
            throw new CallbackException("Service with name '$callback' hasn't found.");
        }
        $callback = $container->get($callback);

        return new $class($callback, $expression);
    }
}
