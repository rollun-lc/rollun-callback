<?php

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
            \rollun\callback\Callback\Pipe\CronReceiver::class =>
                \rollun\callback\Callback\Pipe\Factory\CronReceiverFactory::class,
            \rollun\callback\Callback\Pipe\HttpReceiver::class =>
                \rollun\callback\Callback\Pipe\Factory\HttpReceiverFactory::class
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
            'name' => 'api.http.callback',
            'path' => '/api/http/callback',
            'middleware' => \rollun\callback\Callback\Pipe\HttpReceiver::class,
            'allowed_methods' => ['POST'],
        ],
        [
            'name' => 'api.cron',
            'path' => '/api/cron',
            'middleware' => \rollun\callback\Callback\Pipe\CronReceiver::class,
            'allowed_methods' => ['GET', 'POST'],
        ],
    ],
];
