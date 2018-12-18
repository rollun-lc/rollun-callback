<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter;

use Psr\Log\LoggerInterface;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;
use rollun\utils\Json\Serializer;

class QueueFiller implements InterrupterInterface
{
    /** @var QueueInterface */
    protected $queue;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ServiceQueue constructor.
     * @param QueueInterface $queue
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(QueueInterface $queue, LoggerInterface $logger = null)
    {
        $this->queue = $queue;
        InsideConstruct::setConstructParams(["logger" => LoggerInterface::class]);
    }

    /**
     * @param $message
     * @return string
     * @throws \rollun\utils\Json\Exception
     */
    public static function serializeMessage($message)
    {
        return base64_encode(Serializer::jsonSerialize($message));
    }

    /**
     * @param $message
     * @return mixed
     */
    public static function unserializeMessage($message)
    {
        return Serializer::jsonUnserialize(base64_decode($message));
    }

    /**
     * @param mixed $value
     * @return mixed
     * @throws \rollun\utils\Json\Exception
     */
    public function __invoke($value)
    {
        $message = static::serializeMessage($value);
        $this->queue->addMessage($message);
        $this->logger->info("add message to queue: {queue}", [
            "message" => $message,
            "queue" => $this->queue->getName()
        ]);
        return [];
    }

    /**
     * ["callback", "queue"]
     * @return array
     */
    public function __sleep()
    {
        return ["queue"];
    }

    /**
     * Resume callback and queue
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(["logger" => LoggerInterface::class]);
    }
}
