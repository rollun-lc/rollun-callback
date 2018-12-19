<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use rollun\callback\Callback\Interrupter\InterrupterAbstract;
use rollun\callback\Callback\Interrupter\Job;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueException;
use rollun\callback\Queues\QueueInterface;

class Extractor
{
    const KEY_MESSAGE_ID = 'message_id';

    /** @var QueueInterface */
    protected $queue;

    /**
     * Extractor constructor.
     * @param QueueInterface $queue
     */
    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Extract queue and call callback
     * @return bool
     * @throws QueueException
     */
    public function extract()
    {
        try {
            $message = $this->queue->getMessage();
            if (isset($message)) {
                $job = Job::unserializeBase64($message->getData());

                try {
                    $resp = call_user_func($job->getCallback(), $job->getValue());
                } catch (\Throwable $e) {
                    throw new QueueException("Function error. " . $e->getMessage(), $e->getCode(), $e);
                }

                return $resp;
            }
        } catch (\Throwable $e) {
            throw new QueueException(
                "Can't extract from queue '{$this->queue->getName()}'. Reason: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return null;
    }

    /**
     * @param $value
     * @return array array contains field
     * array contains field
     * @throws QueueException
     */
    public function __invoke($value = null)
    {
        $return = [];
        try {
            /** @var Message $message */
            $message = $this->queue->getMessage();

            if (isset($message)) {
                $job = Job::unserializeBase64($message->getData());
                $result[static::KEY_MESSAGE_ID] = $message->getId();

                try {
                    $result['data'][] = call_user_func($job->getCallback(), $job->getValue());
                } catch (\Throwable $e) {
                    $result['data'][] = $e;
                }
            }

            $result[InterrupterAbstract::INTERRUPTER_TYPE_KEY] = static::class;
        } catch (\Throwable $e) {
            throw new QueueException("Extract queue error!", $e->getCode(), $e);
        }

        return $return;
    }
}
