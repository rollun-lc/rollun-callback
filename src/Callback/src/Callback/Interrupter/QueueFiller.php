<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter;

use Jaeger\Tag\StringTag;
use Jaeger\Tracer\Tracer;
use Psr\Log\LoggerInterface;
use ReflectionException;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;
use rollun\utils\Json\Exception;
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
     * @var Tracer
     */
    protected $tracer;

    /**
     * ServiceQueue constructor.
     * @param QueueInterface $queue
     * @param LoggerInterface|null $logger
     * @throws ReflectionException
     */
    public function __construct(QueueInterface $queue, LoggerInterface $logger = null, Tracer $tracer = null)
    {
        $this->queue = $queue;
        InsideConstruct::init(['logger' => LoggerInterface::class, 'tracer' => Tracer::class]);
    }

    /**
     * @param $message
     * @return string
     * @throws Exception
     */
    public static function serializeMessage($message): string
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
     * @return PayloadInterface
     * @throws Exception
     */
    public function __invoke($value): PayloadInterface
    {
        $span = $this->tracer->start('QueueFiller::__invoke', [
            new StringTag('queue', $this->queue->getName()),
            new StringTag('value', Serializer::jsonSerialize($value))
        ]);
        $serializedData = static::serializeMessage($value);
        $message = new Message(['Body' => $serializedData, 'TracerContext' => base64_encode(Serializer::jsonSerialize($span->getContext()))]);
        $payload = [
            'message' => $value,
            'queue' => $this->queue->getName(),
        ];

        $this->queue->addMessage($message);
        $this->logger->info('Add message to queue', [
            'message' => $message,
        ]);
        $this->tracer->finish($span);
        return new SimplePayload(null, $payload);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['queue'];
    }

    /**
     * Resume callback and queue
     * @throws ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(['logger' => LoggerInterface::class, 'tracer' => Tracer::class]);
    }
}
