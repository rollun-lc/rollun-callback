<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;
use Zend\Log\Writer\WriterInterface;

/**
 * Class Worker
 * @package rollun\callback\Callback
 */
class Worker
{
    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * @var SerializedCallback
     */
    protected $callback;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Worker constructor.
     * @param QueueInterface $queue
     * @param callable $callback
     * @param WriterInterface|null $writer
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(
        QueueInterface $queue,
        callable $callback,
        WriterInterface $writer = null,
        LoggerInterface $logger = null
    ) {
        $this->queue = $queue;

        if (!$callback instanceof SerializedCallback) {
            $callback = new SerializedCallback($callback);
        }

        $this->writer = $writer;
        $this->callback = $callback;
        InsideConstruct::setConstructParams(["logger" => LoggerInterface::class]);
    }

    /**
     * Fetch value from queue and apply callable for it
     *
     * @return array|PayloadInterface|null
     */
    public function __invoke()
    {
        if ($this->queue->isEmpty()) {
            $this->logger->debug("Queue {queue} is empty. Worker not started.", [
                "queue" => $this->queue->getName(),
            ]);
            return null;
        }

        $message = $this->queue->getMessage();

        try {
            $value = $this->unserialize($message->getData());
            $payload = $this->callback->__invoke($value);
            $this->queue->deleteMessage($message);
        } catch (\Throwable $throwable) {
            $payload = [
                "message" => $message ? $message->getId() : null,
                "queue" => $this->queue->getName(),
                "exception" => $throwable,
            ];
            $this->logger->warning("Worker failed execute callback", $payload);
        }

        if ($this->writer) {
            $event = is_array($payload) ? $payload : (array)$payload;
            $this->writer->write($event);
        }

        return $payload;
    }

    /**
     * Unserialize message data
     * @param $data string
     * @return mixed
     */
    private function unserialize(string $data)
    {
        return QueueFiller::unserializeMessage($data);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ["queue", "callback", "writer"];
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(["logger" => LoggerInterface::class]);
    }
}
