<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.10.17
 * Time: 18:37
 */

namespace rollun\callback\Queues;

abstract class AbstractSerializedQueue extends AbstractQueue
{
    /**
     * @return array
     */
    public function __sleep()
    {
        return [
            "delaySeconds",
            "queueName",
            "priorityHandlerClass",
        ];
    }

    /**
     * unserialize call Queue constructor
     */
    public function __wakeup()
    {
        $this->__construct($this->queueName, $this->delaySeconds, $this->priorityHandlerClass);
    }
}
