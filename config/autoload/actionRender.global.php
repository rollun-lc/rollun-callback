<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 23.01.17
 * Time: 17:41
 */

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadResponseRendererAbstractFactory;
use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;

return [
    'dependencies' => [
        'abstract_factories' => [
            \rollun\actionrender\Factory\MiddlewarePipeAbstractFactory::class,
            \rollun\actionrender\Factory\ActionRenderAbstractFactory::class,
            \rollun\actionrender\Factory\LazyLoadResponseRendererAbstractFactory::class
        ],
        'invokables' => [
            \rollun\actionrender\Renderer\Html\HtmlParamResolver::class =>
                \rollun\actionrender\Renderer\Html\HtmlParamResolver::class,
            \rollun\actionrender\Renderer\Json\JsonRendererAction::class =>
                \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
            \rollun\actionrender\ReturnMiddleware::class =>
                \rollun\actionrender\ReturnMiddleware::class
        ],
        'factories' => [
            \rollun\actionrender\Renderer\Html\HtmlRendererAction::class =>
                \rollun\actionrender\Renderer\Html\HtmlRendererFactory::class
        ],
    ],
    MiddlewarePipeAbstractFactory::KEY => [
        'htmlReturner' => [
            'middlewares' => [
                \rollun\actionrender\Renderer\Html\HtmlParamResolver::class,
                \rollun\actionrender\Renderer\Html\HtmlRendererAction::class
            ]
        ]
    ],
    LazyLoadResponseRendererAbstractFactory::KEY => [
        'simpleHtmlJsonRenderer' => [
            LazyLoadResponseRendererAbstractFactory::KEY_ACCEPT_TYPE_PATTERN => [
                //pattern => middleware-Service-Name
                '/application\/json/' => \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
                '/text\/html/' => 'htmlReturner'
            ]
        ]
    ],
    ActionRenderAbstractFactory::KEY => [
        /*'home' => [
            ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => '',
            ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'simpleHtmlJsonRenderer'
        ],*/
    ]
];
