<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Middleware;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Class WebhookMiddlewareFactory
 * @package rollun\callback\Middleware
 */
class WebhookMiddlewareFactory
{
    public const MIDDLEWARES = 'middleware';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     * @return WebhookMiddleware
     */
    public function __invoke(ContainerInterface $container): WebhookMiddleware
    {
        $this->container = $container;
        $config = $this->getConfig();
        $middlewares = $this->resolveMiddlewares($config[self::MIDDLEWARES] ?? []);
        $interrupterMiddleware = $container->get(InterrupterMiddleware::class);
        $metricMiddleware = $container->get(MetricMiddleware::class);

        return new WebhookMiddleware($interrupterMiddleware, $metricMiddleware, null, $middlewares);
    }

    private function resolveMiddlewares(array $middlewares): array
    {
        return array_map(function ($middleware) {
            if (is_string($middleware)) {
                return $this->container->get($middleware);
            }
            if ($middleware instanceof MiddlewareInterface) {
                return $middleware;
            }
            throw new InvalidArgumentException('Wrong middleware.');
        }, $middlewares);
    }

    private function getConfig(): array
    {
        $config = $this->container->get('config');
        return $config[self::class] ?? [];
    }
}
