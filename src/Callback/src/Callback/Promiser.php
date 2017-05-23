<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Callback;

use rollun\logger\Exception\LogExceptionLevel;
use rollun\logger\Exception\LoggedException;
use rollun\promise\Promise\Exception;
use Opis\Closure\SerializableClosure;
use rollun\promise\Promise\Promise;
use rollun\callback\Callback\Interruptor\Process as InterruptorProcess;

/**
 * Callback
 *
 * @category   callback
 * @package    zaboy
 */
class Promiser extends Callback implements PromiserInterface
{

    /**
     *
     * @var InterruptorProcess
     */
    protected $interruptorProcess;

    /**
     *
     * @var Promise
     */
    protected $interruptorResalt;

    /**
     *
     * @var Promise
     */
    protected $promise;

    public function __construct(callable $callable)
    {
        parent::__construct($callable);
        $this->promise = new Promise; //$iPromise->then([$this, 'run']);
        $this->interruptorProcess = new InterruptorProcess([$this, 'runInProcess']);
    }

    public function __invoke($value)
    {

        if (isset($this->interruptorResalt) && is_array($this->interruptorResalt)) {
            throw new LoggedException('Do not call twise __invoke()', LogExceptionLevel::WARNING);
        }
        $result = call_user_func($this->interruptorProcess, $value);
        if (isset($this->interruptorResalt)) {
            $this->interruptorResalt->resolve($result);
        } else {
            $this->interruptorResalt = $result;
        }
        return $this->promise;
    }

    public function runInProcess($value)
    {
        try {
            $result = $this->run($value);
            $this->promise->resolve($result);
            return $result;
        } catch (Exception $e) {
            $this->promise->reject($e);
            return [];
        }
    }

    public function __sleep()
    {
        if ($this->interruptorProcess instanceof \Closure) {
            $this->interruptorProcess = new SerializableClosure($this->interruptorProcess);
        }

        $array = parent::__sleep();
        $array[] = 'interruptorProcess';
        $array[] = 'interruptorResalt';
        $array[] = 'promise';

        return $array;
    }

    public function getInterruptorResult()
    {
        if (!isset($this->interruptorResalt) || is_array($this->interruptorResalt)) {
            $promise = new Promise;
            if (is_array($this->interruptorResalt)) {
                $promise->resolve($this->interruptorResalt);
            }
            $this->interruptorResalt = $promise;
        } else {
            $promise = $this->interruptorResalt;
        }
        return $promise;
    }

}
