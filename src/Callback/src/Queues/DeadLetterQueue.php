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
    public const QUEUE_NAME = 'DeadLetterQueue';

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
        $queueName = sprintf('%s_for_%s', self::QUEUE_NAME, $adapterName);

        $sqsAdapter = new SqsAdapter($sqsClientConfig);
        $this->queueArn = $sqsAdapter->getQueueArn($queueName);
        parent::__construct($sqsAdapter, $queueName);
    }

    public function getQueueArn(): string
    {
        return $this->queueArn;
    }
}
