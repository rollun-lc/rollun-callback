<?php

return [
    'services' => [
        'aliases' => [
            //this 'callback' is service name in url
            'promiseDbAdapter' => constant('APP_ENV') === 'production' ? 'db' : 'testDb',
        ],
    ],
];
