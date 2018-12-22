<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Factory;

use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Interop\Container\ContainerInterface;

abstract class CallbackAbstractFactoryAbstract implements AbstractFactoryInterface
{
    const KEY = 'callback';

    const KEY_CLASS = 'class';

    const DEFAULT_CLASS = null;

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
