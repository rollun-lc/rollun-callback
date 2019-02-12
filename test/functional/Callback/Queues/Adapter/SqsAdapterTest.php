<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\Callback\Queues\Adapter;

use Aws\Sqs\SqsClient;
use Psr\Container\ContainerInterface;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use rollun\callback\Queues\Adapter\SqsAdapter;

class SqsAdapterTest extends AbstractAdapterTest
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function createObject($timeInFlight = 0): AdapterInterface
    {
        $sqsClient = SqsClient::factory([
            'key' => getenv('AWS_KEY'),
            'secret'  => getenv('AWS_SECRET'),
            'region' => getenv('AWS_REGION'),
        ]);

        return new SqsAdapter($sqsClient, null, [
            'VisibilityTimeout' => $timeInFlight,
        ]);
    }

    protected function getContainer()
    {
        if ($this->container == null) {
            $this->container = require 'config/container.php';
        }

        return $this->container;
    }

    public function testDeadLetterQueue()
    {
//        $testQueue = 'testQueue';
//        $deadLetterQueueName = 'deadLetter';
//        $maxReceiveCount = 5;
//        $sqsClient = SqsClient::factory([
//            'key' => getenv('AWS_KEY'),
//            'secret'  => getenv('AWS_SECRET'),
//            'region' => getenv('AWS_REGION'),
//        ]);
//
//        /** @var SqsAdapter $sqsAdapter */
//        $sqsAdapter = $this->getContainer()->get('testDeadLetterSqsAdapter');
//
//
//        // Create queue if not exist.
//        $queues = $sqsAdapter->listQueues();
//        if (!in_array($testQueue, $queues)) {
//            $sqsAdapter->createQueue($testQueue);
//        }
//
//        $sqsAdapter->createQueue($testQueue);
//        $sqsAdapter->addMessage($testQueue, 'a');
//
//        while ($maxReceiveCount) {
//            $maxReceiveCount--;
//            $sqsAdapter->getMessages($testQueue);
//            sleep(1);
//        }
//
//        $message = false;
//
//        while (!$message) {
//            sleep(20);
//            $queueUrl = $sqsClient->getQueueUrl([
//                'QueueName' => $deadLetterQueueName,
//            ])->get('QueueUrl');
//            $results = $sqsClient->receiveMessage([
//                'QueueUrl' => $queueUrl,
//                'MaxNumberOfMessages' => 1,
//            ]);
//            $messages = $results->get('Messages') ?? [];
//
//            $message = array_shift($messages);
//            $message = unserialize($message['Body']);
//        }
//
//        $this->assertEquals($message, 'a');
//
//        $sqsAdapter->deleteQueue($testQueue);
//        $queueUrl = $sqsClient->getQueueUrl([
//            'QueueName' => $deadLetterQueueName,
//        ])->get('QueueUrl');
//        $sqsClient->deleteQueue([
//            'QueueUrl' => $queueUrl,
//        ]);
    }
}
