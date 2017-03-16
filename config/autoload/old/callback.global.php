<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 27.01.17
 * Time: 11:26
 */

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadPipeAbstractFactory;
use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AbstractLazyLoadMiddlewareGetterAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AttributeAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AttributeSwitchAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\ResponseRendererAbstractFactory;
use rollun\callback\Callback\Interruptor\Factory\AbstractInterruptorAbstractFactory;
use rollun\callback\Callback\Interruptor\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Interruptor\Factory\TickerAbstractFactory;
use rollun\callback\Example;
use rollun\callback\LazyLoadInterruptMiddlewareGetter;

return [
    AbstractInterruptorAbstractFactory::KEY => [
        'sec_multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Example\CronSecMultiplexer::class,
        ],
        'min_multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Example\CronMinMultiplexer::class,
            MultiplexerAbstractFactory::KEY_INTERRUPTERS_SERVICE => [
                'cron_sec_ticker'
            ]
        ],
        'cron_sec_ticker' => [
            TickerAbstractFactory::KEY_CLASS => \rollun\callback\Callback\Interruptor\Ticker::class,
            TickerAbstractFactory::KEY_WRAPPER_CLASS => \rollun\callback\Callback\Interruptor\Process::class,
            TickerAbstractFactory::KEY_CALLBACK => 'sec_multiplexer',
        ],
        'cron' => [
            TickerAbstractFactory::KEY_CLASS => \rollun\callback\Callback\Interruptor\Ticker::class,
            TickerAbstractFactory::KEY_WRAPPER_CLASS => \rollun\callback\Callback\Interruptor\Process::class,
            TickerAbstractFactory::KEY_CALLBACK => 'min_multiplexer',
            TickerAbstractFactory::KEY_TICKS_COUNT => 1,
        ]
    ],

    'dependencies' => [
        'invokables' => [
            LazyLoadInterruptMiddlewareGetter::class =>
                LazyLoadInterruptMiddlewareGetter::class,
            'httpCallback' =>
                \rollun\callback\Middleware\HttpInterruptorAction::class,
            \rollun\datastore\Middleware\ResourceResolver::class =>
                \rollun\datastore\Middleware\ResourceResolver::class,
            \rollun\datastore\Middleware\RequestDecoder::class => \rollun\datastore\Middleware\RequestDecoder::class,
        ],
        'factories' => [
            \rollun\datastore\Middleware\HtmlDataStoreRendererAction::class =>
                \rollun\datastore\Middleware\Factory\HtmlDataStoreRendererFactory::class
        ],
        'abstract_factories' => [
            MiddlewarePipeAbstractFactory::class,
            ActionRenderAbstractFactory::class,
            AttributeAbstractFactory::class,
            ResponseRendererAbstractFactory::class,
            LazyLoadPipeAbstractFactory::class,
            AttributeSwitchAbstractFactory::class,
            \rollun\callback\Callback\Interruptor\Factory\MultiplexerAbstractFactory::class,
            \rollun\callback\Callback\Interruptor\Factory\TickerAbstractFactory::class,
        ]
    ],

    AbstractLazyLoadMiddlewareGetterAbstractFactory::KEY => [
        'webhookJsonRender' => [
            ResponseRendererAbstractFactory::KEY_MIDDLEWARE => [
                '/application\/json/' => \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
            ],
            ResponseRendererAbstractFactory::KEY_CLASS => \rollun\actionrender\LazyLoadMiddlewareGetter\ResponseRenderer::class,
        ],
    ],

    LazyLoadPipeAbstractFactory::KEY => [
        'webhookLLPipe' => LazyLoadInterruptMiddlewareGetter::class,
        'webhookJsonRenderLLPipe' => 'webhookJsonRender'
    ],

    ActionRenderAbstractFactory::KEY => [
        'webhookActionRender' => [
            ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'webhookLLPipe',
            ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'webhookJsonRenderLLPipe'
        ],
    ],
];
