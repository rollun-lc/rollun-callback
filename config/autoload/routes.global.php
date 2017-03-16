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

    ],
];
