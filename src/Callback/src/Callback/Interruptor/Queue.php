<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.17
 * Time: 14:57
 */

namespace rollun\callback\Callback\Interruptor;

use rollun\callback\Callback\Callback;
use rollun\callback\Queues\QueueInterface;

class Queue extends InterruptorAbstract
{
    /** @var  QueueInterface */
    protected $queue;

    public function __construct(callable $callback, QueueInterface $queue)
    {
        parent::__construct($callback);
        $this->queue = $queue;
    }

    /**
     * @param \rollun\callback\Callback\mix $value
     * @return mixed
     */
    public function __invoke($value)
    {
        $callback = $this->getCallback();

        $job = new Job($callback, $value);
        $this->queue->addMessage($job->serializeBase64());

        $result[static::MACHINE_NAME_KEY] = constant(static::ENV_VAR_MACHINE_NAME);

        $result[static::INTERRUPTOR_TYPE_KEY] = static::class;
        return $result;
    }
}
