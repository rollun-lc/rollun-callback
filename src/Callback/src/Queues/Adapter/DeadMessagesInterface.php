<?php

namespace rollun\callback\Queues\Adapter;

interface DeadMessagesInterface
{

    /**
     * @param string $queueName
     *
     * @return int
     */
    public function getNumberDeadMessages(string $queueName): int;

    /**
     * @param string $queueName
     * @param int    $nbMsg
     *
     * @return array
     */
    public function getDeadMessages(string $queueName, int $nbMsg = 1): array;

    /**
     * @param string $queueName
     * @param int    $nbMsg
     */
    public function deleteDeadMessages(string $queueName, int $nbMsg = 1);

}