<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\callback\Queues;

/**
 * Every implementation of this interface must have possibility to serialize
 *
 * Interface QueueInterface
 * @package rollun\callback\Queues
 */
interface QueueInterface
{
    /**
     * Pop message from queue
     * If queue empty or message can't be fetch in any reason throw QueueException
     * Use isEmpty() for check if queue has messages
     *
     * @param null $priority
     * @return Message
     * @throws QueueException
     */
    public function getMessage($priority = null): ?Message;

    /**
     * @param null $priority
     * @return int
     */
    public function getNumberMessages($priority = null): int;

    /**
     * Add message to queue
     * If message can't be added in any reason throw QueueException
     *
     * @param Message $message
     * @param null $priority
     * @return mixed
     */
    public function addMessage(Message $message, $priority = null): void;

    /**
     * @param Message $message
     * @return void
     */
    public function deleteMessage(Message $message);

    /**
     * Get queue name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Clear queue
     * Return true if success and false if failed
     *
     * @return mixed
     */
    public function purgeQueue(): void;

    /**
     * @return bool
     */
    public function isEmpty(): bool;
}
