<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Queues;

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

    public function getData()
    {
        if (isset($this->message['Body'])) {
            return $this->message['Body'];
        }
        throw new \RuntimeException('No "Body" in the message');
    }

    public function getMessage()
    {
        return $this->message;
    }

}
