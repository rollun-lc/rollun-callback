<?php

/**
 * Created by PhpStorm.
 * User: itprofessor02
 * Date: 21.03.19
 * Time: 16:58
 */

namespace rollun\callback\Callback;

/**
 * Class CompositionCallback
 * @package rollun\callback\Callback
 * Two callable(function) composition
 * Example
 * Has two callable:
 * A -> B
 * B -> C
 * with use this composition callback we can create callable (function)
 * A -> C
 *
 */
class Composition
{
    /**
     * @var callable
     */
    private $callables;

    /**
     * CompositionCallback constructor.
     * @param callable ...$callables
     */
    public function __construct(callable ...$callables)
    {
        $this->callables = $callables;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function __invoke($value)
    {
        try {
            return array_reduce($this->callables, static fn($value, $callable) => $callable($value), $value);
        } catch (\Throwable $exception) {
            throw new \RuntimeException("Has error by call callable.", $exception->getCode(), $exception);
        }
    }
}
