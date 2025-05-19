<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller;

use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueClient as BaseClient;
use rollun\callback\Queues\QueueException;
use Throwable;

class QueueClient extends BaseClient
{
    public const KEY_DELAY_SECOND = 'delaySecond';

    public function __construct(AdapterInterface $adapter, string $queueName)
    {
        parent::__construct($adapter, $queueName, null);
    }

    /**
     * @inheritdoc
     */
    public function addMessage(Message $message, $priority = null): void
    {
        $messageData = $message->getMessage();

        if (isset($messageData[self::KEY_DELAY_SECOND])) {
            $delaySeconds = (int) $messageData[self::KEY_DELAY_SECOND];
            unset($messageData[self::KEY_DELAY_SECOND]);
        } else {
            $delaySeconds = 0;
        }

        try {
            $this->queueClient->addMessage($this->getName(), $messageData, $priority, $delaySeconds);
        } catch (Throwable $t) {
            throw new QueueException("Can't add message to queue. Reason: " . $t->getMessage(), 0, $t);
        }
    }
}
