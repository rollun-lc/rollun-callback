<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use Psr\Log\LoggerInterface;
use ReflectionException;
use rollun\callback\Callback\Multiplexer\CallbackObject;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
use rollun\dic\InsideConstruct;

class Multiplexer
{
    /**
     * @var CallbackObject[]
     */
    protected $callbacks;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string|null
     */
    private $name;

    /**
     * Multiplexer constructor.
     * @param LoggerInterface|null $logger
     * @param array $callbacks
     * @param string|null $name multiplexer name
     */
    public function __construct(LoggerInterface $logger, array $callbacks = [], ?string $name = null)
    {
        $this->logger = $logger;
        $this->name = empty($name) ? 'undefined' : $name;

        ksort($callbacks);
        foreach ($callbacks as $key => $callback) {
            $this->addCallback($callback);
        }
    }

    /**
     * @param $value
     * @return array|PayloadInterface
     */
    public function __invoke($value = null)
    {
        $result = [];
        ksort($this->callbacks);
        $interrupterWasCalled = false;

        foreach ($this->callbacks as $key => $callback) {
            try {
                $result[$key] = $callback->runCallback($value);
            } catch (\Throwable $e) {
                $this->logger->error(
                    "Get error '{$e->getMessage()}' by handle '{$key}' callback service.", [
                        'exception' => $e,
                        'multiplexer' => $this->name
                    ]
                );
                $result[$key] = $e;
            }

            if ($result[$key] instanceof PayloadInterface) {
                $result[$key] = $result[$key]->getPayload();
                $interrupterWasCalled = true;
            }
        }

        if ($interrupterWasCalled) {
            $result = new SimplePayload(null, $result);
        }

        return $result;
    }

    /**
     * @param $callback
     * @param null $priority
     */
    public function addCallback($callback, $priority = null)
    {
        $callback = $this->resolveCallback($callback);
        if (is_null($callback)) {
            return;
        }

        if (isset($priority)) {
            if (array_key_exists($priority, $this->callbacks)) {
                $this->addCallback($this->callbacks[$priority], $priority + 1);
            }

            $this->callbacks[$priority] = $callback;
        } else {
            $this->callbacks[] = $callback;
        }
    }

    /**
     * Wrapped callbacks into rollun\callback\Callback\Multiplexer\CallbackObject
     *
     * @param $callback
     * @return CallbackObject|null
     */
    protected function resolveCallback($callback): ?CallbackObject
    {
        if ($callback instanceof CallbackObject) {
            return $callback;
        }

        if (is_callable($callback)) {
            if (!$callback instanceof SerializedCallback) {
                $callback = new SerializedCallback($callback);
            }
            return new CallbackObject($callback);
        }

        if (is_array($callback)) {
            try {
                return CallbackObject::createFromArray($callback);
            } catch (\InvalidArgumentException $e) {
                $this->logger->error("Cannot resolve callback: malformed multiplexer callback array structure.", [
                    'multiplexer' => $this->name,
                    'array' => $callback,
                    'exception' => $e
                ]);
            }

            return null;
        }

        $this->logger->error("Cannot resolve callback: callable or array expected.", [
            'multiplexer' => $this->name,
        ]);

        return null;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ["callbacks"];
    }

    /**
     * @throws ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(["logger" => LoggerInterface::class]);
    }
}
