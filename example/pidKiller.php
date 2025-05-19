<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\PidKiller\Worker;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueClient;
use rollun\dic\InsideConstruct;
use rollun\logger\Writer\Stream;

include 'vendor/autoload.php';
$container = include 'config/container.php';
InsideConstruct::setContainer($container);

$callable = function ($name): void {
    echo "Hello $name";
};

// specify concrete queue adapter for queue
$fileAdapter = new FileAdapter('/tmp/test');
$queueName = 'testQueue';

$queue = new QueueClient($fileAdapter, $queueName);

$queue->addMessage(Message::createInstance(QueueFiller::serializeMessage('John')));
$queue->addMessage(Message::createInstance(QueueFiller::serializeMessage('Alisa')));
$queue->addMessage(Message::createInstance(QueueFiller::serializeMessage('Bob')));

$worker = new Worker($queue, $callable, null);

// Worker fetch one message from queue and run callable with received message as param
echo $worker() . PHP_EOL; // Hello John
echo $worker() . PHP_EOL; // Hello Alisa
echo $worker() . PHP_EOL; // Hello Bob

echo PHP_EOL;

// If you specify writer, when callable will execute the result will be written to source that concrete write determinate
$writer = new Stream('php://stdout');
$worker = new Worker($queue, $callable, $writer);

$queue->addMessage(Message::createInstance(QueueFiller::serializeMessage('John')));
$queue->addMessage(Message::createInstance(QueueFiller::serializeMessage('Alisa')));
$queue->addMessage(Message::createInstance(QueueFiller::serializeMessage('Bob')));


// Cause writer writes to stdout, you probably will see pretty much as in previous case
$worker(); // Hello John%timestamp% %level% (%priority%): %message% %context%
$worker(); // Hello Alisa%timestamp% %level% (%priority%): %message% %context%
$worker(); // Hello Bob%timestamp% %level% (%priority%): %message% %context%

// P.S. Set some formatters and filter to have output format you want
