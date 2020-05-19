<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Middleware;

use Psr\Container\ContainerInterface;

/**
 * Class WebhookMiddlewareFactory
 * @package rollun\callback\Middleware
 */
class WebhookMiddlewareFactory
{
    /**
     * @param ContainerInterface $container
     * @return WebhookMiddleware
     */
    public function __invoke(ContainerInterface $container): WebhookMiddleware
    {
        $interrupterMiddleware = $container->get(InterrupterMiddleware::class);
        $metricMiddleware = $container->get(MetricMiddleware::class);

        return new WebhookMiddleware($interrupterMiddleware, $metricMiddleware);
    }
}
