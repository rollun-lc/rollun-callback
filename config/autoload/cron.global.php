<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20.01.17
 * Time: 14:06
 */

use rollun\callback\Middleware\CronReceiver;
use rollun\callback\Example;

return [
    'dependencies' => [
        //example cron multiplexer
        'invokables' => [
            'exampleSecMultiplexor' => Example\CronSecMultiplexer::class,
            'exampleMinMultiplexor' => Example\CronMinMultiplexer::class
        ],
    ],
    'cron' => [
        CronReceiver::KEY_MIN_MULTIPLEXER => 'exampleMinMultiplexor',
        CronReceiver::KEY_SEC_MULTIPLEXER => 'exampleSecMultiplexor',
    ],
];