<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 18.10.17
 * Time: 18:28
 */

namespace rollun\callback\Queues;

use ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler;
use ReputationVIP\QueueClient\QueueClient;
use ReputationVIP\QueueClient\QueueClientInterface;

abstract class AbstractQueue implements QueueInterface
{
    /** @var QueueClient */
    protected $queueClient;

    /** @var int */
    protected $delaySeconds;

    /** @var string */
    protected $queueName;

    /** @var string */
    protected $priorityHandlerClass;

    /**
     * AbstractQueue constructor.
     * @param $queueName
     * @param int $delaySeconds
     * @param string $priorityHandlerClass
     */
    abstract public function __construct(
        $queueName,
        $delaySeconds = 0,
        $priorityHandlerClass = StandardPriorityHandler::class
    );

    /**
     * Check if queue is empty.
     * @return bool
     */
    public function isEmpty()
    {
        foreach ($this->queueClient->getPriorityHandler()->getAll() as $priority) {
            if(!$this->queueClient->isEmpty($this->queueName, $priority)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param null $priority
     * @return null|\rollun\callback\Queues\Message
     */
    public function getMessage($priority = null)
    {
        if(is_null($priority)) {
            foreach ($this->queueClient->getPriorityHandler()->getAll() as $priority) {
                if(!$this->queueClient->isEmpty($this->queueName, $priority)) {
                    return $this->receiveMessage($priority);
                }
            }
            return null;
        }
        return $this->receiveMessage($priority);
    }

    /**
     * Receove message and remove form queue.
     * @param $priority
     * @return null|Message
     */
    protected function receiveMessage($priority) {
        $messages = $this->queueClient->getMessages($this->queueName, 1, $priority);
        if (isset($messages[0])) {
            $message = new Message($messages[0]);
            $this->queueClient->deleteMessage($this->queueName, $messages[0]);
        } else {
            $message = null;
        }
        return $message;
    }

    /**
     * @param $message
     * @param null $priority
     * @return QueueInterface
     */
    public function addMessage($message, $priority = null)
    {
        $this->queueClient->addMessage($this->queueName, $message, $priority, $this->delaySeconds);
        return $this;
    }

    /**
     * @param $queueName
     * @param null $priority
     * @return QueueInterface
     */
    public function purgeQueue($queueName, $priority = null)
    {
        $this->queueClient->purgeQueue($queueName, $priority);
        return $this;
    }

    /**
     * Return queue name;
     * @return string
     */
    public function getName() {
        return $this->queueName;
    }

}
