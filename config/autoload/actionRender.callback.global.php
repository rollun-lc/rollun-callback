<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 27.01.17
 * Time: 11:26
 */

use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;
use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Renderer\ResponseRendererAbstractFactory;
use rollun\callback\Middleware\CronReceiver;
use rollun\callback\Example;

return [
    'cron' => [
        CronReceiver::KEY_MIN_MULTIPLEXER => 'exampleMinMultiplexor',
        CronReceiver::KEY_SEC_MULTIPLEXER => 'exampleSecMultiplexor',
    ],

    'dependencies' => [
        'invokables' => [
            'exampleSecMultiplexor' => Example\CronSecMultiplexer::class,
            'exampleMinMultiplexor' => Example\CronMinMultiplexer::class,
            \rollun\callback\Middleware\HttpCallbackReceiver::class =>
                \rollun\callback\Middleware\HttpCallbackReceiver::class,
        ],
        'factories' => [
            \rollun\callback\Middleware\CronReceiver::class =>
                \rollun\callback\Middleware\Factory\CronReceiverFactory::class
        ],
    ],
    MiddlewarePipeAbstractFactory::KEY_AMP => [

    ],
    ResponseRendererAbstractFactory::KEY_RESPONSE_RENDERER => [
        'interruptJsonRender' => [
            ResponseRendererAbstractFactory::KEY_ACCEPT_TYPE_PATTERN => [
                //pattern => middleware-Service-Name
                '/application\/json/' => \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
            ]
        ]
    ],
    ActionRenderAbstractFactory::KEY_AR_SERVICE => [
        'interrupt-cron' => [
            ActionRenderAbstractFactory::KEY_AR_MIDDLEWARE => [
                ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE =>
                    \rollun\callback\Middleware\CronReceiver::class,
                ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'interruptJsonRender'
            ]
        ],
        'interrupt-callback' => [
            ActionRenderAbstractFactory::KEY_AR_MIDDLEWARE => [
                ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE =>
                    \rollun\callback\Middleware\HttpCallbackReceiver::class,
                ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'interruptJsonRender'
            ]
        ],
    ]
];
