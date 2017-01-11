<?php

return [
    'services' => [
        'aliases' => [
            //this 'callback' is service name in url
            'entityDbAdapter' => constant('APP_ENV') === 'production' ? 'db' : 'testDb',
        ],
    ],
];
