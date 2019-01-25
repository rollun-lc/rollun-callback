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
    const KEY_DELAY_SECOND = 'delaySecond';

    public function __construct(AdapterInterface $adapter, string $queueName)
    {
        parent::__construct($adapter, $queueName, null);
    }

    /**
     * @inheritdoc
     */
    public function addMessage(Message $message, $priority = null): void
    {
        $message = $message->getMessage();

        if (isset($message[self::KEY_DELAY_SECOND])) {
            $delaySeconds = intval($message[self::KEY_DELAY_SECOND]);
            unset($message[self::KEY_DELAY_SECOND]);
        } else {
            $delaySeconds = 0;
        }

        try {
            $this->queueClient->addMessage($this->getName(), $message, $priority, $delaySeconds);
        } catch (Throwable $t) {
            throw new QueueException("Can't add message to queue. Reason: " . $t->getMessage(), 0, $t);
        }
    }
}
