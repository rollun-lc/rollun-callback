<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.01.17
 * Time: 16:40
 */

namespace rollun\callback\Callback;

use Psr\Log\LoggerInterface;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interruptor\Job;
use rollun\dic\InsideConstruct;

class Multiplexer implements CallbackInterface
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
     * @param callable[] $callbacks
     * @param LoggerInterface|null $logger
     * @throws \ReflectionException
     */
    public function __construct(array $callbacks = [], LoggerInterface $logger = null)
    {
        if (!$this->checkCallable($callbacks)) {
            throw new CallbackException('Interruptors array contains non InterruptorInterface object!');
        }
        InsideConstruct::setConstructParams(["logger" => LoggerInterface::class]);
        $this->callbacks = $callbacks;
    }

    /**
     * Multiplexer constructor.
     * @param callable[] $callbacks
     * @return bool
     */
    protected function checkCallable(array $callbacks)
    {
        foreach ($callbacks as $key => $callback) {
            if (!is_callable($callback)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $value
     * @return array
     */
    public function __invoke($value)
    {
        $result = [];
        ksort($this->callbacks);
        foreach ($this->callbacks as $key => $callback) {
            try {
                $result['data'][$key] = $callback($value);
            } catch (\Exception $e) {
                $this->logger->error("Get error {message} by handle '$key' callback service.", [
                    "code" => $e->getCode(),
                    "line" => $e->getLine(),
                    "file" => $e->getFile(),
                    "message" => $e->getMessage()
                ]);
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
        return ["callable"];
    }

    /**
     *
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(["logger" => LoggerInterface::class]);
    }
}
