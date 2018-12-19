<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use rollun\callback\Callback\Example\CallMe;
use rollun\callback\Callback\Factory\SerializedCallbackAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\InterruptAbstractFactoryAbstract;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Queues\Factory\FileAdapterAbstractFactory;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;
use rollun\callback\Queues\Factory\SqsAdapterAbstractFactory;

return [
    'dependencies' => [
        'invokables' => [
            CallMe::class => CallMe::class,
        ],
    ],

    SerializedCallbackAbstractFactory::class => [
        'testCallback' => [
            SerializedCallbackAbstractFactory::KEY_CALLBACK_METHOD => '__invoke',
            SerializedCallbackAbstractFactory::KEY_SERVICE_NAME => CallMe::class,
        ],
    ],
    InterruptAbstractFactoryAbstract::KEY => [
        'testInterrupter' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'testCallback',
        ],
    ],
    InterruptAbstractFactoryAbstract::KEY => [
        'testInterrupter' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'testCallback',
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
