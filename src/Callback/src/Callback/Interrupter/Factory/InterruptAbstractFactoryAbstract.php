<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter\Factory;

use rollun\callback\Callback\Interrupter\InterrupterInterface;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;

abstract class InterruptAbstractFactoryAbstract implements AbstractFactoryInterface
{
    public const KEY = 'interrupt';

    public const KEY_CLASS = 'class';

    public const KEY_CALLBACK_SERVICE = 'callbackService';

    public const DEFAULT_CLASS = InterrupterInterface::class;

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $class = $container->get('config')[static::KEY][$requestedName][static::KEY_CLASS] ?? null;

        return is_a($class, static::DEFAULT_CLASS, true);
    }
}
