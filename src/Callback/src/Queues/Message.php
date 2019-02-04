<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types = 1);

namespace rollun\callback\Queues;

use InvalidArgumentException;

class Message
{
    /**
     * @var array
     *
     * Example SqsQueue message response
     *  [
     *      'id' => test_queue100586ba95da73a60.15840006,
     *      'time-in-flight' => 1483450832,
     *      'delayed-until' => 1483450717,
     *      'Body' => test1,
     *      'priority' => 100,
     *  ]
     */
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @param $message
     * @return Message
     */
    static function createInstance($message)
    {
        return new self($message);
    }

    /**
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getData()
    {
        if (isset($this->message['Body'])) {
            return $this->message['Body'];
        }

        throw new InvalidArgumentException('No "Body" in the message');
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function getId()
    {
        if (isset($this->message['id'])) {
            return $this->message['id'];
        }

        throw new InvalidArgumentException('No "id" in the message');
    }

    /**
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }
}
