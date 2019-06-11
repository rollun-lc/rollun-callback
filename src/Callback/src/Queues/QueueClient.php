<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\callback\Queues;

use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use ReputationVIP\QueueClient\QueueClient as ExternalQueueClient;
use Throwable;

class QueueClient implements QueueInterface
{
    /**
     * @var ExternalQueueClient
     */
    protected $queueClient;

    /**
     * @var int
     */
    protected $delaySeconds;

    /**
     * @var string
     */
    protected $queueName;

    /**
     * AbstractQueue constructor.
     * @param AdapterInterface $adapter
     * @param string $queueName
     * @param int $delaySeconds
     */
    public function __construct(AdapterInterface $adapter, string $queueName, $delaySeconds = 0)
    {
        $this->queueName = $queueName;
        $this->delaySeconds = $delaySeconds;
        $this->queueClient = new ExternalQueueClient($adapter);

        // Create queue if not exist.
        //FIXME: Maybe problem if create queue implicit in runtime.
        $queues = $this->queueClient->listQueues();
        if (!in_array($this->queueName, $queues, true)) {
            $this->queueClient->createQueue($this->queueName);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMessage($priority = null): ?Message
    {
        try {
            if ($priority === null && $this->queueClient->getPriorityHandler() !== null) {
                /** @noinspection SuspiciousLoopInspection */
                foreach ($this->queueClient->getPriorityHandler()->getAll() as $priority) {
                    if (!$this->queueClient->isEmpty($this->getName(), $priority)) {
                        return $this->receiveMessage($priority);
                    }
                }
                return null;
            }

            return $this->receiveMessage($priority);
        } catch (Throwable $t) {
            throw new QueueException(
                "Can't get message from queue '{$this->getName()}'. Reason: " . $t->getMessage(),
                0,
                $t
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMessage(Message $message)
    {
        $this->queueClient->deleteMessage($this->getName(), $message->getMessage());
    }

    /**
     * Pop message from queue
     *
     * @param $priority
     * @return Message|null
     */
    protected function receiveMessage($priority): ?Message
    {
        $messages = $this->queueClient->getMessages($this->getName(), 1, $priority);

        if (isset($messages[0])) {
            $message = new Message($messages[0]);
        } else {
            $message = null;
        }

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function addMessage(Message $message, $priority = null): void
    {
        try {
            $this->queueClient->addMessage($this->getName(), $message->getMessage(), $priority, $this->delaySeconds);
        } catch (Throwable $t) {
            throw new QueueException("Can't add message to queue. Reason: " . $t->getMessage(), 0, $t);
        }
    }

    /**
     * @inheritdoc
     */
    public function isEmpty(): bool
    {
        if ($this->queueClient->getPriorityHandler() !== null) {
            foreach ($this->queueClient->getPriorityHandler()->getAll() as $priority) {
                if (!$this->queueClient->isEmpty($this->getName(), $priority)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function purgeQueue($priority = null): void
    {
        $this->queueClient->purgeQueue($this->getName(), $priority);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->queueName;
    }

    public function getNumberMessages($priority = null): int
    {
        return (int)$this->queueClient->getNumberMessages($this->queueName, $priority);
    }
}
