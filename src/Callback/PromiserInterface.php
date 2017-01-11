<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.01.17
 * Time: 13:06
 */

namespace rollun\callback\Callback;

use zaboy\async\Promise\Promise;

interface PromiserInterface
{
    /**
     * PromiserInterface constructor.
     * @param callable $callable
     */
    public function __construct(callable $callable);

    /**
     * @return array|Promise
     */
    public function getInterruptorResult();

    /**
     * @param $value
     * @return array
     */
    public function runInProcess($value);
}
