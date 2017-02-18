<?php

use rollun\callback\Middleware\CronReceiver;
use rollun\callback\Middleware\Factory\CronReceiverFactory;

return [
    'dependencies' => [
        'invokables' => [
            Zend\Expressive\Router\RouterInterface::class => Zend\Expressive\Router\FastRouteRouter::class,
        ],
        'factories' => [

        ],
    ],

    'routes' => [
        /*
         * if you use rollun-datastore uncomment this
         [
            'name' => 'api.rest',
            'path' => '/api/rest[/{resourceName}[/{id}]]',
            'middleware' => 'api-rest',
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        ],
        */
        [
            'name' => 'interrupt.cron',
            'path' => '/interrupt/cron',
            'middleware' => 'interrupt-cron',
            'allowed_methods' => ['GET', 'POST'],
        ],
        [
            'name' => 'interrupt.callback',
            'path' => '/interrupt/callback',
            'middleware' => 'interrupt-callback',
            'allowed_methods' => ['POST'],
        ],
    ],
];
