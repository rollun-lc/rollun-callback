<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.01.17
 * Time: 16:40
 */

namespace rollun\callback\Callback\Interruptor;

use rollun\promise\Promise\Exception;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\InterruptorInterface;
use rollun\callback\Callback\PromiserInterface;

class Multiplexer implements InterruptorInterface
{
    /**
     * @var InterruptorInterface|PromiserInterface[]
     */
    protected $interruptors;

    /**
     * Multiplexer constructor.
     * @param InterruptorInterface[] $interruptors
     * @throws CallbackException
     */
    public function __construct(array $interruptors)
    {
        if (!$this->checkInterruptors($interruptors)) {
            throw new CallbackException('Interruptors array contains non InterruptorInterface object!');
        }
        $this->interruptors = $interruptors;
    }

    /**
     * @param $value
     * @return array
     */
    public function __invoke($value)
    {
        $result = [];
        foreach ($this->interruptors as $interruptor) {
            try {
                $result['data'][] = $interruptor($value);
            }catch (\Exception $e){
                $result['data'][] = $e;
            }
        }

        $result[InterruptorAbstract::MACHINE_NAME_KEY] = constant(InterruptorAbstract::ENV_VAR_MACHINE_NAME);
        $result[InterruptorAbstract::INTERRUPTOR_TYPE_KEY] = static::class;
        return $result;
    }

    /**
     * Multiplexer constructor.
     * @param InterruptorInterface[] $interruptors
     * @return bool
     */
    protected function checkInterruptors(array $interruptors)
    {
        foreach ($interruptors as $interruptor) {
            if (!($interruptor instanceof InterruptorInterface) && !($interruptor instanceof PromiserInterface)){
                return false;
            }
        }
        return true;
    }
}
