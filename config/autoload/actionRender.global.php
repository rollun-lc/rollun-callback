<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 23.01.17
 * Time: 17:41
 */

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadPipeAbstractFactory;
use rollun\actionrender\Factory\LazyLoadResponseRendererAbstractFactory;
use rollun\actionrender\Factory\MiddlewarePipeAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AbstractLazyLoadMiddlewareGetterAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AttributeAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\ResponseRendererAbstractFactory;

return [
    'dependencies' => [
        'abstract_factories' => [
            MiddlewarePipeAbstractFactory::class,
            ActionRenderAbstractFactory::class,
        ],
        'invokables' => [
            \rollun\actionrender\Renderer\Html\HtmlParamResolver::class =>
                \rollun\actionrender\Renderer\Html\HtmlParamResolver::class,
            \rollun\actionrender\Renderer\Json\JsonRendererAction::class =>
                \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
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

    AbstractLazyLoadMiddlewareGetterAbstractFactory::KEY => [

        'simpleHtmlJsonRenderer' => [
            ResponseRendererAbstractFactory::KEY_MIDDLEWARE => [
                '/application\/json/' => \rollun\actionrender\Renderer\Json\JsonRendererAction::class,
                '/text\/html/' => 'htmlReturner'
            ],
            ResponseRendererAbstractFactory::KEY_CLASS => \rollun\actionrender\LazyLoadMiddlewareGetter\ResponseRenderer::class,
        ],
        ''
    ],

    LazyLoadPipeAbstractFactory::KEY => [
        'simpleHtmlJsonRendererLLPipe' => 'simpleHtmlJsonRenderer'
    ],

    ActionRenderAbstractFactory::KEY => [
        /*'home-page' => [
                ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => '',
                ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => 'simpleHtmlJsonRenderer'
        ],*/
    ]
];
