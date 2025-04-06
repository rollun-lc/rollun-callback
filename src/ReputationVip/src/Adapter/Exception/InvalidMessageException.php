<?php

namespace ReputationVIP\QueueClient\Adapter\Exception;

use ReputationVIP\QueueClient\Exception\QueueClientException;

class InvalidMessageException extends \InvalidArgumentException implements QueueClientException
{
    /**
     * @param null|array $queueMessage
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(protected $queueMessage, $message = "", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array|null
     */
    public function getQueueMessage()
    {
        return $this->queueMessage;
    }
}
