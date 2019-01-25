<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\Promise\SimplePayload;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;

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
     * @var LinuxPidKiller
     */
    protected $linuxPidKiller;

    /** @var integer */
    protected $processLifetime;

    /**
     * @var SerializedCallback
     */
    protected $process;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Worker constructor.
     * @param QueueInterface $queue
     * @param LinuxPidKiller $linuxPidKiller
     * @param Process $process
     * @param int $processLifetime
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(
        QueueInterface $queue,
        LinuxPidKiller $linuxPidKiller,
        Process $process,
        int $processLifetime,
        LoggerInterface $logger = null
    ) {
        $this->queue = $queue;
        $this->linuxPidKiller = $linuxPidKiller;
        $this->processLifetime = $processLifetime;

        if (!$process instanceof SerializedCallback) {
            $process = new SerializedCallback($process);
        }

        $this->process = $process;
        InsideConstruct::setConstructParams(["logger" => LoggerInterface::class]);
    }

    /**
     * Fetch value from queue and apply callable for it
     *
     * @return array|SimplePayload
     */
    public function __invoke()
    {
        if ($this->queue->isEmpty()) {
            $this->logger->info("Queue {queue} is empty. Worker not started.", [
                "queue" => $this->queue->getName(),
            ]);
        }

        $message = $this->queue->getMessage();
        $value = $this->unserialize($message->getData());

        try {
            $payload = $this->process->__invoke($value);
            $this->linuxPidKiller->create([
                'pid' => $payload->getId(),
                'lstart' => time(),
                'delaySeconds' => $this->processLifetime
            ]);
        } catch (\Throwable $throwable) {
            $payload = [
                "message_id" => $message->getId(),
                "data" => $message->getData(),
                "queue" => $this->queue->getName(),
                "exception" => $throwable,
            ];
            $this->logger->error("By handled message {message_id} from {queue} get {exception} ", $payload);
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
        return ["pidQueue", "workQueue", "process"];
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(["logger" => LoggerInterface::class]);
    }
}
