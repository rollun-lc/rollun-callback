<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.06.18
 * Time: 16:56
 */

namespace rollun\callback\Callback\Interruptor;


use Psr\Log\LoggerInterface;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;
use rollun\utils\Json\Serializer;

class ServiceQueue implements InterruptorInterface
{
	/** @var QueueInterface */
	protected $queue;

	/**
	 * @var null
	 */
	protected $priority = null;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * ServiceQueue constructor.
	 * @param QueueInterface $queue
	 * @param LoggerInterface|null $logger
	 * @param null $priority
	 * @throws \ReflectionException
	 */
	public function __construct(QueueInterface $queue, $priority = null, LoggerInterface $logger = null)
	{
		$this->queue = $queue;
		$this->priority = $priority;
		InsideConstruct::setConstructParams(["logger" => LoggerInterface::class]);
	}

    /**
     * @param $message
     * @throws \rollun\utils\Json\Exception
     */
    static public function serializeMessage($message){
        return base64_encode(Serializer::jsonSerialize($message));
    }

    /**
     * @param $message
     */
    static public function unserializeMessage($message){
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