<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use Zend\Db\Adapter\AdapterInterface;

return [
    'dependencies' => [
        'aliases' => [
            'db' => AdapterInterface::class,
        ],
    ],
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'Pdo_Mysql',
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
        'hostname' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT') ?: 3306,
    ],
];
