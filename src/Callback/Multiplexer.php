<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.01.17
 * Time: 16:40
 */

namespace rollun\callback\Callback;

use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\PromiserInterface;

//TODO: обернуть в процес вызов всех переданых callback
class Multiplexer implements CallbackInterface
{
    /**
     * @var callable[]
     */
    protected $callbacks;

    /**
     * Multiplexer constructor.
     * @param callable[] $callbacks
     * @throws CallbackException
     */
    public function __construct(array $callbacks = [])
    {
        if (!$this->checkCallable($callbacks)) {
            throw new CallbackException('Interruptors array contains non InterruptorInterface object!');
        }
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
        foreach ($this->callbacks as $callback) {
            try {
                $result['data'][] = $callback($value);
            } catch (\Exception $e) {
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
}
