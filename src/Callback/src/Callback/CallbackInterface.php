<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 21.03.17
 * Time: 13:29
 */

namespace rollun\callback\Callback;

interface CallbackInterface
{
    /**
     * Do callback
     * @param $value
     * @return
     */
    public function __invoke($value);
}
