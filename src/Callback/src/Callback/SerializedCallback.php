<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use Opis\Closure\SerializableClosure;

/**
 * Class SerializedCallback
 * @package rollun\callback\Callback
 */
final class SerializedCallback
{
    /**
     * @var Callable
     */
    protected $callback;

    /**
     * SerializedCallback constructor.
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->setCallback($callback);
    }

    /**
     * @param mixed $value
     * @return mixed
     * @throws CallbackException
     */
    public function __invoke($value)
    {
        if (!is_callable($this->getCallback(), true)) {
            throw new CallbackException(
                'There was not correct instance callable in Callback'
            );
        }
        try {
            $callback = $this->getCallback();
            $result = call_user_func($callback, $value);

            return $result;
        } catch (\Exception $exc) {
            throw new CallbackException(
                'Cannot execute Callback. Reason: ' . $exc->getMessage(),
                $exc->getCode(),
                $exc
            );
        }
    }

    protected function getCallback()
    {
        return $this->callback;
    }

    protected function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __sleep()
    {
        $callback = $this->getCallback();
        if (is_array($callback)) {
            list($context, $method) = $callback;
            $callback = function ($value) use ($context, $method) {
                return call_user_func([$context, $method], $value);
            };
        }
        if ($callback instanceof \Closure) {
            $callback = new SerializableClosure($callback);
            $this->setCallback($callback);
        }

        return ['callback'];
    }

    public function __wakeup()
    {
        $callback = $this->getCallback();
        if (!is_callable($callback, true)) {
            throw new CallbackException(
                'There is not correct instance callable in Callback'
            );
        }
    }
}
