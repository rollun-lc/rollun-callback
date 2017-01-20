<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.01.17
 * Time: 13:18
 */

namespace rollun\callback\Callback\Interruptor;

interface InterruptorInterface
{
    /**
     * @param $value
     * @return array
     * array contains field
     *
     */
    public function __invoke($value);
}
