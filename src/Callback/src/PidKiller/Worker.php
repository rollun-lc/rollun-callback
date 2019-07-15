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

/**
 * Class Worker
 * @package rollun\callback\Callback
 */
class Worker implements InfoProviderInterface
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
     * @var string
     */
    private $info;

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
        LoggerInterface $logger = null,
        string $info = ""
    ) {
        $this->queue = $queue;

        if (!$callback instanceof SerializedCallback) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $callback = new SerializedCallback($callback);
        }

        $this->writer = $writer;
        $this->callback = $callback;
        InsideConstruct::setConstructParams(['logger' => LoggerInterface::class]);
        $this->info = $info;
    }

    /**
     * Fetch value from queue and apply callable for it
     *
     * @return array|PayloadInterface|null
     */
    public function __invoke()
    {
        if (!$message = $this->queue->getMessage()) {
            $this->logger->debug('Queue {queue} is empty. Worker not started.', [
                'queue' => $this->queue->getName(),
            ]);
            return null;
        }

        try {
            $value = $this->unserialize($message->getData());
            $payload = $this->callback->__invoke($value);
            if ($this->writer) {
                $event = is_array($payload) ? $payload : (array)$payload;
                $this->writer->write($event);
            }
            $this->queue->deleteMessage($message);
        } catch (\Throwable $throwable) {
            $payload = [
                'message' => $message ? $message->getMessage() : null,
                'queue' => $this->queue->getName(),
                'exception' => $throwable,
            ];
            $this->logger->warning('Worker failed execute callback', $payload);
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
        return ['queue', 'callback', 'writer'];
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(['logger' => LoggerInterface::class]);
    }

    public function getInfo(): string
    {
        return $this->info;
    }
}
