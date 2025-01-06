<?php
declare(strict_types=1);

namespace rollun\callback;

use ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler;
use ReputationVIP\QueueClient\PriorityHandler\ThreeLevelPriorityHandler;
use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Factory\ProxyAbstractFactory;
use rollun\callback\Callback\Factory\SerializedCallbackAbstractFactory;
use rollun\callback\Callback\Factory\TickerAbstractFactory;
use rollun\callback\Callback\Factory\CronExpressionAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\HttpAbstractFactory;
use rollun\callback\Callback\Factory\HttpClientAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\ProcessByNameAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\QueueJobFillerAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\QueueMessageFillerAbstractFactory;
use rollun\callback\Callback\Ping;
use rollun\callback\Middleware\GetParamsResolver;
use rollun\callback\Middleware\InterrupterMiddleware;
use rollun\callback\Middleware\InterrupterMiddlewareFactory;
use rollun\callback\Middleware\CallablePluginManager;
use rollun\callback\Middleware\CallablePluginManagerFactory;
use rollun\callback\Middleware\JsonRenderer;
use rollun\callback\Middleware\MetricMiddleware;
use rollun\callback\Middleware\PostParamsResolver;
use rollun\callback\Middleware\WebhookMiddleware;
use rollun\callback\Middleware\WebhookMiddlewareFactory;
use rollun\callback\PidKiller\Factory\WorkerAbstractFactory;
use rollun\callback\PidKiller\Factory\WorkerManagerAbstractFactory;
use rollun\callback\PidKiller\Factory\WorkerProducerAbstractFactory;
use rollun\callback\PidKiller\Factory\WorkerSystemAbstractFactory;
use rollun\callback\PidKiller\LinuxPidKiller;
use rollun\callback\PidKiller\PidKillerInterface;
use rollun\callback\PidKiller\ProcessManager;
use rollun\callback\PidKiller\QueueClient;
use rollun\callback\Queues\Factory\FileAdapterAbstractFactory;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use rollun\callback\Queues\Factory\SqsAdapterAbstractFactory;
use rollun\callback\Queues\Factory\DbAdapterAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

/**
 * Class ConfigProvider
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class ConfigProvider
{
    const PID_KILLER_SERVICE = 'pidKillerService';

    public function __invoke()
    {
        return [
            'dependencies'                                 => [
                'abstract_factories' => [
                    // Queues
                    QueueClientAbstractFactory::class,
                    FileAdapterAbstractFactory::class,
                    SqsAdapterAbstractFactory::class,
                    DbAdapterAbstractFactory::class,

                    // Interrupters
                    HttpAbstractFactory::class,
                    ProxyAbstractFactory::class,
                    HttpClientAbstractFactory::class,
                    ProcessAbstractFactory::class,
                    ProcessByNameAbstractFactory::class,
                    QueueJobFillerAbstractFactory::class,
                    QueueMessageFillerAbstractFactory::class,

                    // Callback
                    MultiplexerAbstractFactory::class,
                    SerializedCallbackAbstractFactory::class,
                    TickerAbstractFactory::class,
                    CronExpressionAbstractFactory::class,

                    // Pidkiller
                    WorkerAbstractFactory::class,
                    WorkerManagerAbstractFactory::class,
                    WorkerProducerAbstractFactory::class,
                    WorkerSystemAbstractFactory::class,
                ],
                'invokables'         => [
                    GetParamsResolver::class         => GetParamsResolver::class,
                    PostParamsResolver::class        => PostParamsResolver::class,
                    JsonRenderer::class              => JsonRenderer::class,
                    StandardPriorityHandler::class   => StandardPriorityHandler::class,
                    ThreeLevelPriorityHandler::class => ThreeLevelPriorityHandler::class,
                    ProcessManager::class            => ProcessManager::class,
                    MetricMiddleware::class          => MetricMiddleware::class
                ],
                "factories"          => [
                    InterrupterMiddleware::class => InterrupterMiddlewareFactory::class,
                    WebhookMiddleware::class     => WebhookMiddlewareFactory::class,
                    CallablePluginManager::class => CallablePluginManagerFactory::class,
                    LinuxPidKiller::class        => InvokableFactory::class,
                ],
                'aliases'            => [
                    self::PID_KILLER_SERVICE  => LinuxPidKiller::class,
                    PidKillerInterface::class => self::PID_KILLER_SERVICE,
                ],
            ],
            CallablePluginManagerFactory::KEY_INTERRUPTERS => [
                'abstract_factories' => [
                    // Interrupters
                    HttpAbstractFactory::class,
                    ProxyAbstractFactory::class,
                    HttpClientAbstractFactory::class,
                    ProcessAbstractFactory::class,
                    QueueJobFillerAbstractFactory::class,
                    QueueMessageFillerAbstractFactory::class,

                    // Callback
                    MultiplexerAbstractFactory::class,
                    SerializedCallbackAbstractFactory::class,
                    TickerAbstractFactory::class,
                    CronExpressionAbstractFactory::class,
                ],
                'invokables'         => [
                    Ping::class => Ping::class
                ],
                'aliases'            => [
                    'ping' => Ping::class
                ]
            ],
            SqsAdapterAbstractFactory::class               => [
                'pidQueueAdapter' => [
                    SqsAdapterAbstractFactory::KEY_SQS_CLIENT_CONFIG => [
                        'key'    => getenv('AWS_KEY'),
                        'secret' => getenv('AWS_SECRET'),
                        'region' => getenv('AWS_REGION'),
                    ],
                ],
            ],
            QueueClientAbstractFactory::class              => [
                'pidKillerQueue' => [
                    QueueClientAbstractFactory::KEY_CLASS   => QueueClient::class,
                    QueueClientAbstractFactory::KEY_ADAPTER => 'pidQueueAdapter',
                    QueueClientAbstractFactory::KEY_NAME    => 'pidqueue',
                ],
            ],
        ];
    }
}
