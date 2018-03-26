<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Queues;


use rollun\callback\Callback\CallbackException;

class Message
{
//TODO: counter of return in queue
    /**
     *
     * @var array [id] => test_queue100586ba95da73a60.15840006 [time-in-flight] => 1483450832 [delayed-until] => 1483450717 [Body] => test1 [priority] => 100
     */
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     * @throws CallbackException
     */
    public function getData()
    {
        if (isset($this->message['Body'])) {
            return $this->message['Body'];
        }
        throw new CallbackException('No "Body" in the message');
    }

    /**
     * @return string
     * @throws CallbackException
     */
    public function getId()
    {
        if (isset($this->message['id'])) {
            return $this->message['id'];
        }
        throw new CallbackException('No "id" in the message');
    }

    /**
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }

}
