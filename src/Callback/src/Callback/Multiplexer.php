<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use Psr\Log\LoggerInterface;
use ReflectionException;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
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
            if (is_callable($callback)) {
                $this->addCallback($callback);
            } else {
                $this->logger->error('Wrong callback! Callable expected.');
            }
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
                $result[$key] = $callback($value);
            } catch (\Throwable $e) {
                $this->logger->error(
                    "Get error '{$e->getMessage()}' by handle '{$key}' callback service.", ['exception' => $e]
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
