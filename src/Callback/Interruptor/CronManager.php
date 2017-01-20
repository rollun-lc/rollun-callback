<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20.01.17
 * Time: 12:08
 */

namespace rollun\callback\Callback\Interruptor;

use rollun\callback\Callback\Interruptor\Multiplexer;

class CronManager implements InterruptorInterface
{

    /** @var  Ticker */
    protected $minTicker;

    /**
     * CronManager constructor.
     * @param \rollun\callback\Callback\Interruptor\Multiplexer $secMultiplexor
     * @param \rollun\callback\Callback\Interruptor\Multiplexer $minMultiplexor
     */
    public function __construct(Multiplexer $secMultiplexor, Multiplexer $minMultiplexor)
    {
        /* I guarantee that the multiplexer will not be called more than a second/minute. */
        $secTicker = new Ticker(new Process($secMultiplexor));
        $minMultiplexor->addInterruptor($secTicker);
        $this->minTicker = new Ticker(new Process($minMultiplexor), 1);
    }

    /**
     * @param $value
     * @return array
     * array contains field
     *
     */
    public function __invoke($value)
    {
        return call_user_func($this->minTicker, $value);
    }
}
