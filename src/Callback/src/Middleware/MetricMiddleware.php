<?php

namespace rollun\callback\Middleware;

use rollun\dic\InsideConstruct;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use rollun\logger\Writer\PrometheusWriter;

/**
 * Class MetricMiddleware
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class MetricMiddleware implements MiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * MetricMiddleware constructor.
     *
     * @param LoggerInterface|null $logger
     *
     * @throws \ReflectionException
     */
    public function __construct(LoggerInterface $logger = null)
    {
        InsideConstruct::setConstructParams(['logger' => LoggerInterface::class]);
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(['logger' => LoggerInterface::class]);
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() == '/api/webhook/cron' && $request->getMethod() == 'GET') {
            if (!empty(getenv('SERVICE_NAME'))) {
                // prepare metric data
                $metricData = [
                    PrometheusWriter::METRIC_ID => 'webhook_cron',
                    PrometheusWriter::VALUE     => 1
                ];

                // send metric
                $this->logger->notice('METRICS_COUNTER', $metricData);
            }
        }

        return $handler->handle($request);
    }
}
