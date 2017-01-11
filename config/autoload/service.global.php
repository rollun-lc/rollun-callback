<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 30.12.16
 * Time: 5:24 PM
 */

return [
    'httpInterruptor' => [
        'url' => 'http://' . constant('HOST') .'/api/http'
    ],
    'cronQueue' => [
        'url' => 'http://' . constant('HOST') .'/api/cron'
    ],
];
