<?php

use rollun\datastore\DataStore\Eav\EavAbstractFactory;

return [
    'services' => [
        'aliases' => [
            //this 'callback' is service name in url
            'entityDbAdapter' => constant('APP_ENV') === 'production' ? 'db' : 'testDb',
            EavAbstractFactory::DB_SERVICE_NAME => getenv('APP_ENV') === 'prod' ? 'db' : 'db',
        ],
    ],
];
