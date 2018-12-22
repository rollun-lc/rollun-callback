<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Middleware;

use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;

class CallablePluginManager extends AbstractPluginManager
{
    public function validate($instance)
    {
        if (is_callable($instance)) {
            return;
        }

        throw new InvalidServiceException(sprintf(
            'Plugin manager "%s" expected callable, but "%s" was received',
            __CLASS__,
            is_object($instance) ? get_class($instance) : gettype($instance)
        ));
    }
}
