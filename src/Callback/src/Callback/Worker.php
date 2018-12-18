<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;

/**
 * Class Worker
 * @package rollun\callback\Callback
 */
class Worker
{
    const WORK_SECOND = 59;

    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @var SerializedCallback
     */
    private $callback;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Worker constructor.
     * @param QueueInterface $queue
     * @param callable $callback
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(QueueInterface $queue, callable $callback, LoggerInterface $logger = null)
    {
        $this->queue = $queue;

        if (!$callback instanceof SerializedCallback) {
            $callback = new SerializedCallback($callback);
        }

        $this->callback = $callback;
        InsideConstruct::setConstructParams(["logger" => LoggerInterface::class]);
    }

    public function __invoke()
    {
        $callTimes = 0;
        $startTime = time();

        while ((time() - $startTime) < self::WORK_SECOND) {
            $message = $this->queue->getMessage();
            $value = $this->unserialize($message->getData());
            try {
                call_user_func($this->callback, $value);
            } catch (\Throwable $throwable) {
                $this->logger->error("By handled message {message_id} from {queue} get {exception} ", [
                    "message_id" => $message->getId(),
                    "data" => $message->getData(),
                    "queue" => $this->queue->getName(),
                    "exception" => $throwable->__toString(),
                    "exception_trace" => $throwable->getTraceAsString()
                ]);
            } finally {
                $callTimes++;
            }
        }

        if (!$callTimes) {
            $this->logger->info("Queue {queue} is empty. Worker not started.", [
                "queue" => $this->queue->getName()
            ]);
        }
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
        return [
            "queue",
            "callback"
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(["logger" => LoggerInterface::class]);
    }
}
