<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Queues;

use ReputationVIP\QueueClient\QueueClient;
use ReputationVIP\QueueClient\QueueClientInterface;
use ReputationVIP\QueueClient\Adapter\FileAdapter;
use ReputationVIP\QueueClient\PriorityHandler\ThreeLevelPriorityHandler;

class FileQueue implements QueueInterface
{
    /**
     *
     * @var string
     */
    protected $queueName;

    /**
     *
     * @var QueueClientInterface
     */
    protected $queueClient;

    /**
     *
     * @var int
     */
    protected $delaySeconds;

    /**
     * Queue constructor.
     * @param $queueName
     * @param int $delaySeconds
     */
    public function __construct($queueName, $delaySeconds = 0)
    {
        $this->queueName = $queueName;
        $this->delaySeconds = $delaySeconds;
        $priorityHandler = new ThreeLevelPriorityHandler();
        $adapter = new FileAdapter($this->getQueueDir(), $priorityHandler);
        $this->queueClient = new QueueClient($adapter);
        $queues = $this->queueClient->listQueues();
        if (!in_array($this->queueName, $queues)) {
            $this->queueClient->createQueue($this->queueName);
        }
    }

    /**
     * @param null $priority
     * @return null|\rollun\callback\Queues\Message
     */
    public function getMessage($priority = null)
    {
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
     * @return QueueClient|QueueClientInterface
     */
    public function addMessage($message, $priority = null)
    {
        return $this->queueClient->addMessage($this->queueName, $message, $priority, $this->delaySeconds);
    }

    /**
     * @param $queueName
     * @param null $priority
     * @return QueueClient|QueueClientInterface
     */
    public function purgeQueue($queueName, $priority = null)
    {
        return $this->queueClient->purgeQueue($queueName, $priority);
    }

    /**
     * @return string
     */
    protected function getQueueDir()
    {
        return 'data' . DIRECTORY_SEPARATOR . 'queues';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->queueName;
    }
}
