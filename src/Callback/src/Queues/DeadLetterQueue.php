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
    public const QUEUE_NAME = 'deadLetterQueue';

    protected const INSIDE_QUEUE_NAME = 'd751713988987e9331980363e24189cedeadLetterQueue';

    /** @var SqsClient */
    private $sqsClient;

    public function __construct($sqsClientConfig)
    {
        $this->sqsClient = SqsClient::factory($sqsClientConfig);
        $sqsAdapter = new SqsAdapter($sqsClientConfig);
        parent::__construct($sqsAdapter, self::QUEUE_NAME);
    }

    public function getQueueArn()
    {
        $queueUrl = $this->sqsClient->getQueueUrl([
            'QueueName' => self::INSIDE_QUEUE_NAME,
        ])->get('QueueUrl');

        return $this->sqsClient->getQueueArn($queueUrl);
    }
}
