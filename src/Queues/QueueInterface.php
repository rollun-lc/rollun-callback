<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.17
 * Time: 15:40
 */
namespace rollun\callback\Queues;

interface QueueInterface
{
    public function getMessage($priority = null);

    public function addMessage($message, $priority = null);
}