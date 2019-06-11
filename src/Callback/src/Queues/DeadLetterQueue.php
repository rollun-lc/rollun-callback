<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Queues;

use Aws\Sqs\SqsClient;
use rollun\callback\Queues\Adapter\SqsAdapter;

class DeadLetterQueue extends QueueClient
{
    public const QUEUE_NAME = 'DLQueue';

    /**
     * @var string
     */
    private $queueArn;

    /**
     * DeadLetterQueue constructor.
     * @param $adapterName
     * @param $sqsClientConfig
     */
    public function __construct($adapterName, $sqsClientConfig)
    {
        $queueName = sprintf('%s_%s', $adapterName, self::QUEUE_NAME);
        $sqsAdapter = new SqsAdapter($sqsClientConfig);
        parent::__construct($sqsAdapter, $queueName);
        $this->queueArn = $sqsAdapter->getQueueArn($queueName);
    }

    public function getQueueArn(): string
    {
        return $this->queueArn;
    }
}
