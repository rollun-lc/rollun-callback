<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller;

use Jaeger\Tag\StringTag;
use Jaeger\Tracer\Tracer;
use Psr\Log\LoggerInterface;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Callback\SerializedCallback;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;
use rollun\logger\Processor\ExceptionBacktrace;
use rollun\utils\Json\Serializer;

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
     * @var Tracer|null
     */
    private $tracer;

    /**
     * Worker constructor.
     * @param QueueInterface $queue
     * @param callable $callback
     * @param WriterInterface|null $writer
     * @param LoggerInterface|null $logger
     * @param Tracer|null $tracer
     * @param string $info
     * @throws \ReflectionException
     */
    public function __construct(
        QueueInterface $queue,
        callable $callback,
        WriterInterface $writer = null,
        LoggerInterface $logger = null,
        Tracer $tracer = null,
        private string $info = ''
    ) {
        $this->queue = $queue;

        if (!$callback instanceof SerializedCallback) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $callback = new SerializedCallback($callback);
        }

        $this->writer = $writer;
        $this->callback = $callback;
        InsideConstruct::init(['logger' => LoggerInterface::class, 'tracer' => Tracer::class]);
    }

    /**
     * Fetch value from queue and apply callable for it
     *
     * @return array|PayloadInterface|null
     */
    public function __invoke()
    {
        $span = $this->tracer->start('Worker::__invoke');

        try {
            $message = $this->queue->getMessage();
        } catch (\Throwable $e) {
            $this->logger->warning('Error while getting message from queue', [
                'queue' => $this->queue->getName(),
                'exception' => $e,
            ]);
            return null;
        }

        if (!$message) {
            $this->logger->debug('Queue {queue} is empty. Worker not started.', [
                'queue' => $this->queue->getName(),
            ]);
            return null;
        }

        $startCallbackSpan = $this->tracer->start('Worker::start_callback', [
            new StringTag('queue', $this->queue->getName()),
        ], $message->getTracerContext());
        try {
            $value = $this->unserialize($message->getData());
            $startCallbackSpan->addTag(new StringTag('value', Serializer::jsonSerialize($value)));

            $payload = $this->callback->__invoke($value);
            if ($this->writer) {
                $event = is_array($payload) ? $payload : (array)$payload;
                $this->writer->write($event);
            }
            $this->queue->deleteMessage($message);
            $this->tracer->finish($startCallbackSpan);
        } catch (\Throwable $throwable) {
            $payload = [
                'message' => $message ? $message->getMessage() : null,
                'queue' => $this->queue->getName(),
                'exception' => $throwable,
            ];
            $startCallbackSpan->addTag(new StringTag('exception', json_encode((new ExceptionBacktrace())->getExceptionBacktrace($throwable))));
            $this->logger->warning('Worker failed execute callback', $payload);
        }
        $this->tracer->finish($span);
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
        InsideConstruct::initWakeup(['logger' => LoggerInterface::class, 'tracer' => Tracer::class]);
    }

    public function getInfo(): string
    {
        return $this->info;
    }
}
