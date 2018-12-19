<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Example;

class CallMe
{
    public function __invoke($val)
    {
        return 'Hello ' . (is_string($val) ? $val : '');
    }

    public function method($val)
    {
        return 'Hello ' . (is_string($val) ? $val : '');
    }

    public static function staticMethod($val)
    {
        return 'Hello ' . (is_string($val) ? $val : '');
    }
}
