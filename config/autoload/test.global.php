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
use rollun\callback\Callback\Interrupter\ProcessByName;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Queues\Factory\FileAdapterAbstractFactory;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use rollun\callback\Queues\Factory\SqsAdapterAbstractFactory;
use rollun\callback\Queues\Factory\DbAdapterAbstractFactory;
use Jaeger\Tracer\Tracer;
use rollun\tracer\TracerFactory;

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
        'webhookCallback' => function($value) {
            switch ($value) {
                case 'primitive':
                    return true;
                    break;
                case 'array':
                    return ['result' => 'success'];
                    break;
                case 'error':
                    return ['error' => 'Test error'];
                    break;
                case 'exception':
                    throw new Exception('Test exception');
            }
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
        'cronCallbackProcessByName' => [
            ProcessAbstractFactory::KEY_CLASS => ProcessByName::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'cronCallback',
        ],
        'testInterrupter' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'testCallback',
        ],
        'cron' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'cronMultiplexer',
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
        ],
        'testSqsQueue' => [
            'sqsClientConfig' => [

            ],
        ],
    ],
    DbAdapterAbstractFactory::class => [
        'testDbQueue' => [
            'timeInflight' => 0,
            'maxReceiveCount' => 1,
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
        'testDbQueueClient' => [
            'name' => 'dbQueue',
            'adapter' => 'testDbQueue',
        ],
    ],
    'dependencies' => [
        "factories" => [
            Tracer::class => TracerFactory::class,
        ],
    ],
];
