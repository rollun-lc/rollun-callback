<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Callback\Example;

class CallMe
{

    public function __invoke($val)
    {
        return 'Hello ' . $val;
    }

    public function method($val)
    {
        return 'Hello ' . $val;
    }

    public static function staticMethod($val)
    {
        return 'Hello ' . $val;
    }

}
