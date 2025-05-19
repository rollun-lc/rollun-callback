<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Queues;

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
     * @param $queueName
     * @param $sqsClientConfig
     */
    public function __construct($queueName, $sqsClientConfig)
    {
        $sqsAdapter = new SqsAdapter($sqsClientConfig);
        parent::__construct($sqsAdapter, $queueName);
        $this->queueArn = $sqsAdapter->getQueueArn($queueName);
    }

    /**
     * @param $adapterName
     * @param $sqsClientConfig
     * @return DeadLetterQueue
     */
    public static function buildForQueueAdapter($adapterName, $sqsClientConfig): DeadLetterQueue
    {
        return new self(self::getNameForQueueAdapter($adapterName), $sqsClientConfig);
    }

    /**
     * @param $adapterName
     * @return string
     */
    public static function getNameForQueueAdapter($adapterName): string
    {
        return sprintf('%s_%s', $adapterName, self::QUEUE_NAME);
    }

    public function getQueueArn(): string
    {
        return $this->queueArn;
    }
}
