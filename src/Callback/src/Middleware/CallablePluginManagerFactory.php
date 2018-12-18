<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Middleware;

use Psr\Container\ContainerInterface;
use Zend\ServiceManager\Config;

class CallablePluginManagerFactory
{
    const KEY_INTERRUPTERS = 'interrupters';

    public function __invoke(ContainerInterface $container)
    {
        $pluginManager = new CallablePluginManager($container);

        // If this is in a zend-mvc application, the ServiceListener will inject
        // merged configuration during bootstrap.
        if ($container->has('ServiceListener')) {
            return $pluginManager;
        }

        // If we do not have a config service, nothing more to do
        if (! $container->has('config')) {
            return $pluginManager;
        }

        $config = $container->get('config');

        // If we do not have validators configuration, nothing more to do
        if (! isset($config[self::KEY_INTERRUPTERS]) || ! is_array($config[self::KEY_INTERRUPTERS])) {
            return $pluginManager;
        }

        // Wire service configuration for validators
        (new Config($config[self::KEY_INTERRUPTERS]))->configureServiceManager($pluginManager);

        return $pluginManager;
    }
}
