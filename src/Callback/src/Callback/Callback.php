<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Callback;

use Closure;
use rollun\callback\Callback\CallbackException;
use Opis\Closure\SerializableClosure;

/**
 * Callback
 *
 * @category   callback
 * @package    zaboy
 */
//TODO: add interface without param
class Callback
{

    /**
     *
     * @var Callable
     */
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->setCallback($callback);
    }

    /**
     *
     * @param mix $value
     * @return mix
     * @throws CallbackException
     */
    public function __invoke($value)
    {
        return $this->run($value);
    }

    protected function run($value)
    {
        if (!is_callable($this->getCallback(), true)) {
            throw new CallbackException(
                'There was not correct instance callable in Callback');
        }
        try {
            $callback = $this->getCallback();
            $result = call_user_func($callback, $value);
            return $result;
        } catch (\Exception $exc) {
            throw new CallbackException(
                'Cannot execute Callback. Reason: ' . $exc->getMessage(),$exc->getCode(),
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
        if(is_array($callback)) {
            list($context, $method) = $callback;
            $callback = function($value) use($context, $method) {
                return call_user_func([$context, $method], $value);
            };
        }
        if ($callback instanceof \Closure) {
            $callback = new SerializableClosure($callback);
            $this->setCallback($callback);
        }
        return array('callback');
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
