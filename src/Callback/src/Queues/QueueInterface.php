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
    /**
     * @param null $priority
     * @return Message
     */
    public function getMessage($priority = null);

    /**
     * @param $message
     * @param null $priority
     * @return mixed
     */
    public function addMessage($message, $priority = null);

	/**
	 * @return string
	 */
    public function getName();
}