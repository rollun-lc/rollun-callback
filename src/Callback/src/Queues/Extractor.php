<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.17
 * Time: 11:56
 */

namespace rollun\callback\Queues;

use rollun\callback\Callback\CallbackInterface;
use rollun\callback\Callback\Interruptor\InterruptorAbstract;
use rollun\callback\Callback\Interruptor\Job;

class Extractor implements CallbackInterface
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
                    throw new QueueException("Function error!", $e->getCode(), $e);
                }
                return $resp;
            }
        } catch (\Throwable $e) {
            throw new QueueException("Extract queue error!", $e->getCode(), $e);
        }
        return null;
    }

    /**
     * @param $value
     * @return array array contains field
     * array contains field
     * @throws QueueException
     */
    public function __invoke($value)
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

            $result[InterruptorAbstract::INTERRUPTOR_TYPE_KEY] = static::class;
            $result[InterruptorAbstract::MACHINE_NAME_KEY] = constant(InterruptorAbstract::ENV_VAR_MACHINE_NAME);
        } catch (\Throwable $e) {
            throw new QueueException("Extract queue error!", $e->getCode(), $e);
        }

        return $return;
    }
}
