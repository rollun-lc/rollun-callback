<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use Psr\Log\LoggerInterface;
use ReflectionException;
use rollun\dic\InsideConstruct;

class Multiplexer
{
    /**
     * @var callable[]
     */
    protected $callbacks;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Multiplexer constructor.
     * @param array $callbacks
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger, array $callbacks = [])
    {
        $this->logger = $logger;

        ksort($callbacks);
        foreach ($callbacks as $callback) {
            $this->addCallback($callback);
        }
    }

    /**
     * @param $value
     * @return array
     */
    public function __invoke($value = null)
    {
        $result = [];
        ksort($this->callbacks);

        foreach ($this->callbacks as $key => $callback) {
            try {
                $result['data'][$key] = $callback($value);
            } catch (\Exception $e) {
                $this->logger->error(
                    "Get error {message} by handle '$key' callback service.",
                    [
                        "code" => $e->getCode(),
                        "line" => $e->getLine(),
                        "file" => $e->getFile(),
                        "message" => $e->getMessage(),
                    ]
                );
                $result['data'][] = $e;
            }
        }

        return $result;
    }

    /**
     * @param callable $callback
     * @param null $priority
     */
    public function addCallback(callable $callback, $priority = null)
    {
        if (isset($priority)) {
            if (array_key_exists($priority, $this->callbacks)) {
                $this->addCallback($this->callbacks[$priority], $priority + 1);
            }

            if (!$callback instanceof SerializedCallback) {
                $callback = new SerializedCallback($callback);
            }

            $this->callbacks[$priority] = $callback;
        } else {
            $this->callbacks[] = $callback;
        }
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
