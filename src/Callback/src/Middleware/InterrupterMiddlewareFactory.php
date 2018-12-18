<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Middleware;

use Psr\Container\ContainerInterface;

/**
 * Class InterrupterMiddlewareFactory
 * @package rollun\callback\Middleware
 */
class InterrupterMiddlewareFactory
{
    /**
     * @param ContainerInterface $container
     * @return InterrupterMiddleware
     */
    public function __invoke(ContainerInterface $container): InterrupterMiddleware
    {
        return new InterrupterMiddleware($container->get(CallablePluginManager::class));
    }
}
