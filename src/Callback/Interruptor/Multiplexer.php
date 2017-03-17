<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.01.17
 * Time: 16:40
 */

namespace rollun\callback\Callback\Interruptor;

use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\PromiserInterface;

//TODO: обернуть в процес вызов всех переданых callback
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
    public function __construct(array $interruptors = [])
    {

        try {
            if (!$this->checkInterruptors($interruptors)) {
                throw new CallbackException('Interruptors array contains non InterruptorInterface object!');
            }
        } catch (\Exception $exception) {
        }
        $this->interruptors = $interruptors;
    }

    /**
     * Multiplexer constructor.
     * @param InterruptorInterface[] $interruptors
     * @return bool
     */
    protected function checkInterruptors(array $interruptors)
    {
        foreach ($interruptors as $key => $interruptor) {
            if (!($interruptor instanceof InterruptorInterface) && !($interruptor instanceof PromiserInterface)) {
                unset($interruptors[$key]);
                return false;
            }
        }
        return true;
    }

    /**
     * @param $value
     * @return array
     */
    public function __invoke($value)
    {
        $result = [];
        ksort($this->interruptors);
        foreach ($this->interruptors as $interruptor) {
            try {
                $result['data'][] = $interruptor($value);
            } catch (\Exception $e) {
                $result['data'][] = $e;
            }
        }
        $result[InterruptorAbstract::MACHINE_NAME_KEY] = constant(InterruptorAbstract::ENV_VAR_MACHINE_NAME);
        $result[InterruptorAbstract::INTERRUPTOR_TYPE_KEY] = static::class;
        return $result;
    }

    /**
     * @param InterruptorInterface $interruptor
     * @param null $priority
     */
    public function addInterruptor(InterruptorInterface $interruptor, $priority = null)
    {
        if (isset($priority)) {
            if (array_key_exists($priority, $this->interruptors)) {
                $this->addInterruptor($this->interruptors[$priority], $priority + 1);
            }
            $this->interruptors[$priority] = $interruptor;
        } else {
            $this->interruptors[] = $interruptor;
        }
    }
}
