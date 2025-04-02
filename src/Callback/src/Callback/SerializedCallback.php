<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

/**
 * Class SerializedCallback
 * @package rollun\callback\Callback
 */
final class SerializedCallback
{
    /**
     * @var Callable|string
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
            return $callback($value);
        } catch (\Exception $exc) {
            throw new CallbackException(
                'Cannot execute Callback. Reason: ' . $exc->getMessage(),
                $exc->getCode(),
                $exc
            );
        }
    }

    /**
     * @return Callable
     */
    protected function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param callable $callback
     */
    protected function setCallback(callable $callback)
    {
        if (is_string($callback)) {
            $callback = \Closure::fromCallable($callback);
        }
        $this->callback = $callback;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $callback = $this->getCallback();

        if (is_array($callback)) {
            [$context, $method] = $callback;
            $callback = static function ($value) use ($context, $method) {
                return $context->$method($value);
            };
        }

        if ($callback instanceof \Closure) {
            $callback = \Opis\Closure\serialize($callback);
            $this->callback = $callback;
        }

        return ['callback'];
    }

    public function __wakeup()
    {
        $callback = $this->callback;

        if (is_string($callback)) {
            $callback = \Opis\Closure\unserialize($this->callback);
        }

        if (!is_callable($callback, true)) {
            throw new CallbackException(
                'There is not correct instance callable in Callback'
            );
        }

        $this->callback = $callback;
    }
}
