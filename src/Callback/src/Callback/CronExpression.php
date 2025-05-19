<?php

namespace rollun\callback\Callback;

use Cron\CronExpression as CronExpressionFactory;
use rollun\dic\Example\SerializedService;

class CronExpression
{
    /**
     * @var callable
     */
    private $func;
    /**
     * @var string
     */
    private $expression;

    public function __construct(callable $func, string $expression)
    {
        $this->func = $func;
        $this->expression = CronExpressionFactory::factory($expression);
    }

    /**
     * @param null $value
     */
    public function __invoke($value = null)
    {
        if ($this->expression->isDue()) {
            call_user_func($this->func, $value);
        }
    }

}
