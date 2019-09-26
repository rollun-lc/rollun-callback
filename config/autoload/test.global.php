<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use rollun\callback\Callback\Factory\CallbackAbstractFactoryAbstract;
use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Factory\SerializedCallbackAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\InterruptAbstractFactoryAbstract;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Queues\Factory\FileAdapterAbstractFactory;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use rollun\callback\Queues\Factory\SqsAdapterAbstractFactory;
use rollun\callback\Queues\Factory\DbAdapterAbstractFactory;

return [
    SerializedCallbackAbstractFactory::class => [
        'testCallback' => function ($value) {
            return 'Hello ' . (is_string($value) ? $value : '');
        },
        'cronCallback' => function ($value) {
            $time = microtime(true);
            file_put_contents(
                'data' . DIRECTORY_SEPARATOR . 'interrupt_min',
                'MIN_FILE_NAME' . ": {$value} [" . microtime(true) . "]\n",
                FILE_APPEND
            );

            return [$time];
        },
    ],
    CallbackAbstractFactoryAbstract::KEY => [
        'cronMultiplexer' => [
            CallbackAbstractFactoryAbstract::KEY_CLASS => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                'cronCallback',
                'cronCallback',
                'cronCallback',
                'cronCallback',
            ],
        ],
    ],
    InterruptAbstractFactoryAbstract::KEY => [
        'testInterrupter' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'testCallback',
        ],
        'cron' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'cronMultiplexer',
        ],
    ],
    SqsAdapterAbstractFactory::class => [
        'testSqsQueue' => [
            'sqsClientConfig' => [

            ],
        ],
    ],
    FileAdapterAbstractFactory::class => [
        'testFileQueue' => [
            'storageDirPath' => 'data',
        ],
    ],
    SqsAdapterAbstractFactory::class => [
        'testDeadLetterSqsAdapter' => [
            'deadLetterQueueName' => 'deadLetter',
            'maxReceiveCount' => 1,
            'sqsClientConfig' => [
                'key' => getenv('AWS_KEY'),
                'secret'  => getenv('AWS_SECRET'),
                'region' => getenv('AWS_REGION'),
            ],
            'sqsAttributes' => [
                'VisibilityTimeout' => 1
            ],
        ]
    ],
    DbAdapterAbstractFactory::class => [
        'requestedServiceName1' => [
               'timeInflight' => 0,
        ]
    ],
    QueueClientAbstractFactory::class => [
        'testSqsQueueClient' => [
            'name' => 'sqsQueue',
            'adapter' => 'testSqsQueue',
        ],
        'testFileQueueClient' => [
            'name' => 'fileQueue',
            'adapter' => 'testFileQueue',
        ],
    ],
];
