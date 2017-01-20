<?php

use rollun\callback\Middleware\CronReceiver;
use rollun\callback\Middleware\Factory\CronReceiverFactory;

return [
    'dependencies' => [
        'invokables' => [
            Zend\Expressive\Router\RouterInterface::class => Zend\Expressive\Router\FastRouteRouter::class,
        ],
        'factories' => [
            /*
             * if you use rollun-datastore uncomment this
             \rollun\datastore\Pipe\RestRql::class => \rollun\datastore\Pipe\Factory\RestRqlFactory::class
             */
            CronReceiver::class => CronReceiverFactory::class
        ],
    ],

    'routes' => [
        /*
         * if you use rollun-datastore uncomment this
         [
            'name' => 'api.rest',
            'path' => '/api/rest[/{Resource-Name}[/{id}]]',
            'middleware' => \rollun\datastore\Pipe\RestRql::class,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        ],
        */
        [
            'name' => 'api.cron',
            'path' => '/api/cron',
            'middleware' => CronReceiver::class,
            'allowed_methods' => ['GET', 'POST'],
        ],
    ],
];
