<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback;

use rollun\callback\Callback\Example\MinCallback;
use rollun\callback\Callback\Example\SecCallback;
use rollun\callback\Callback\Factory\CallbackAbstractFactoryAbstract;
use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Factory\SerializedCallbackAbstractFactory;
use rollun\callback\Callback\Factory\TickerAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\HttpAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\HttpClientAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\InterruptAbstractFactoryAbstract;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\QueueJobFillerAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\QueueMessageFillerAbstractFactory;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Middleware\GetParamsResolver;
use rollun\callback\Middleware\InterrupterMiddleware;
use rollun\callback\Middleware\InterrupterMiddlewareFactory;
use rollun\callback\Middleware\CallablePluginManager;
use rollun\callback\Middleware\CallablePluginManagerFactory;
use rollun\callback\Middleware\JsonRenderer;
use rollun\callback\Middleware\PostParamsResolver;
use rollun\callback\Middleware\WebhookMiddleware;
use rollun\callback\Middleware\WebhookMiddlewareFactory;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dependencies' => [
                'abstract_factories' => [
                    MultiplexerAbstractFactory::class,
                    SerializedCallbackAbstractFactory::class,
                    TickerAbstractFactory::class,
                ],
                'invokables' => [
                    GetParamsResolver::class => GetParamsResolver::class,
                    PostParamsResolver::class => PostParamsResolver::class,
                    JsonRenderer::class => JsonRenderer::class,
                    MinCallback::class => MinCallback::class,
                    SecCallback::class => SecCallback::class,
                ],
                "factories" => [
                    InterrupterMiddleware::class => InterrupterMiddlewareFactory::class,
                    WebhookMiddleware::class => WebhookMiddlewareFactory::class,
                    CallablePluginManager::class => CallablePluginManagerFactory::class,
                ],
            ],
            CallbackAbstractFactoryAbstract::KEY => [
                'min_multiplexer' => [
                    MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
                    MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                        MinCallback::class,
                        MinCallback::class,
                    ],
                ],
            ],
            InterruptAbstractFactoryAbstract::KEY => [
                'cron' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'min_multiplexer',
                ],
            ],
            CallablePluginManagerFactory::KEY_INTERRUPTERS => [
                'abstract_factories' => [
                    HttpAbstractFactory::class,
                    HttpClientAbstractFactory::class,
                    ProcessAbstractFactory::class,
                    QueueJobFillerAbstractFactory::class,
                    QueueMessageFillerAbstractFactory::class,
                ],
            ],
        ];
    }
}
