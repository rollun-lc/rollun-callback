<?php

declare(strict_types=1);

use Laminas\Stdlib\ArrayUtils\MergeRemoveKey;
use Psr\Log\LoggerInterface;
use rollun\logger\Writer\Stream;

return [
    'log' => [
        LoggerInterface::class => [
            'writers' => [
                'stream_stdout' => new MergeRemoveKey(),
                [
                    'name' => Stream::class,
                    'options' => [
                        'stream' => 'data/logs/all.log',
                    ]
                ],
            ],
        ],
    ],
];