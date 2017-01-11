<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Activator;

class Activator
{

    /**
     *
     * @var Callable
     */
    protected $condition;

    /**
     *
     * @var array array of Callback/Interuptor
     */
    protected $interuptors;

    public function __construct(callable $condition, array $interuptors)
    {
        $this->condition = $condition;
        $this->interuptors = $interuptors;
    }

    public function trigger($value = null)
    {
        if ($condition($value)) {
            return $this->activate($value);
        } else {
            return null;
        }
    }

    protected function activate($value = null)
    {
        $results = [];
        foreach ($this->interuptors as $interuptor) {
            $results[] = call_user_func($interuptor, $value);
        }
    }

}
