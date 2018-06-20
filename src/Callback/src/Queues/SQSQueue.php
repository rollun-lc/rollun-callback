<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.10.17
 * Time: 18:31
 */

namespace rollun\callback\Queues;

use Aws\Sqs\SqsClient;
use ReputationVIP\QueueClient\Adapter\SQSAdapter;
use ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler;
use ReputationVIP\QueueClient\QueueClient;
use rollun\dic\InsideConstruct;

class SQSQueue extends AbstractSerializedQueue
{
    const SQS_CLIENT = SqsClient::class;

    /**
     * AbstractQueue constructor.
     * @param $queueName
     * @param int $delaySeconds
     * @param string $priorityHandlerClass
     * @param SqsClient|null $sqsClient
     */
    public function __construct($queueName, $delaySeconds = 0, $priorityHandlerClass = StandardPriorityHandler::class, SqsClient $sqsClient = null)
    {
        $this->queueName = $queueName;
        $this->delaySeconds = $delaySeconds;
        $this->priorityHandlerClass = $priorityHandlerClass;

        //init sqsClient, queue SQSAdapter and queueClient
        $result = InsideConstruct::setConstructParams(["sqsClient" => static::SQS_CLIENT]);
        /** @var SqsClient $sqsClient */
        $sqsClient = $result['sqsClient'];
        $adapter = new SQSAdapter($sqsClient, new $this->priorityHandlerClass);
        $this->queueClient = new QueueClient($adapter);

        //create queue if not exist.
        $queues = $this->queueClient->listQueues();
        if (!in_array($this->queueName, $queues)) {
            $this->queueClient->createQueue($this->queueName);
        }
    }
}
