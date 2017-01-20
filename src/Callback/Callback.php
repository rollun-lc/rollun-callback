<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Callback;

use rollun\callback\Callback\CallbackException;
use Opis\Closure\SerializableClosure;
use rollun\logger\Exception\LogExceptionLevel;
use rollun\promise\Promise\Promise;

/**
 * Callback
 *
 * @category   callback
 * @package    zaboy
 */
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
                'There was not correct instance callable in Callback',
                LogExceptionLevel::CRITICAL
            );
        }
        try {
            $callback = $this->getCallback();
            $result = call_user_func($callback, $value);
            return $result;
        } catch (\Exception $exc) {
            throw new CallbackException(
                'Cannot execute Callback. Reason: ' . $exc->getMessage(),
                LogExceptionLevel::CRITICAL,
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
                'There is not correct instance callable in Callback',
                LogExceptionLevel::CRITICAL
            );
        }
    }

}
