<?php


namespace rollun\callback\Callback;


use Psr\Log\LoggerInterface;
use rollun\callback\Callback\CallbackInterface;
use rollun\callback\Callback\Interruptor\ServiceQueue;
use rollun\callback\Queues\AbstractQueue;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;
use rollun\utils\Json\Serializer;

/**
 * Class Worker
 * @package rollun\accounting\Callback
 */
class Worker implements CallbackInterface
{
    const WORK_SECOND = 59;

    /**
     * @var AbstractQueue
     */
    private $queue;

    /**
     * @var CallbackInterface
     */
    private $callback;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Worker constructor.
     * @param QueueInterface $queue
     * @param CallbackInterface $callback
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(AbstractQueue $queue, CallbackInterface $callback, LoggerInterface $logger = null)
    {
        $this->queue = $queue;
        $this->callback = $callback;
        InsideConstruct::setConstructParams(["logger" => LoggerInterface::class]);
    }

    /**
     * Do callback
     * @param $value
     */
    public function __invoke($value)
    {
        if ($this->queue->isEmpty()) {
            $this->logger->info("Queue {queue} is empty. Worker not started.", [
                "queue" => $this->queue->getName()
            ]);
            return;
        }
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
            }
        }
    }

    /**
     * Unserialize message data
     * @param $data string
     * @return mixed
     */
    private function unserialize(string $data)
    {
        return ServiceQueue::unserializeMessage($data);
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