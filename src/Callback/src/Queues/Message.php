<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace rollun\callback\Queues;

use InvalidArgumentException;
use Jaeger\Span\Context\SpanContext;

class Message
{
    /**
     * @param mixed[] $message
     */
    public function __construct(
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
        protected $message
    )
    {
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

    public function getTracerContext(): ?SpanContext
    {
        if (isset($this->message['TracerContext'])) {
            return \rollun\utils\Json\Serializer::jsonUnserialize(base64_decode($this->message['TracerContext']));
        }
        return null;
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function getId()
    {
        return $this->message['id'] ?? null;
    }

    /**
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }
}
