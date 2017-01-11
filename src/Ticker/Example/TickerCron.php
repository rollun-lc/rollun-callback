<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 04.01.17
 * Time: 17:47
 */

namespace rollun\callback\Ticker\Example;

use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Example\CronMinMultiplexer;
use rollun\callback\Example\CronSecMultiplexer;
use rollun\callback\Ticker\Ticker;
use rollun\utils\UtcTime;

class TickerCron extends Ticker
{
    public function __construct(callable $tickerCallback = null, $ticksCount = 3, $tickDuration = 1)
    {
        parent::__construct($tickerCallback, $ticksCount, $tickDuration);
    }

    public function everySec()
    {
        $cronSecMultiplexor =  new CronSecMultiplexer([]);
        $cronSecMultiplexor('');
        return UtcTime::getUtcTimestamp(5);
    }

    public function everyMin()
    {
        $cronMinMultiplexor =  new CronMinMultiplexer([
            new Process(function () {
                $this->secBySec60ticks();
            })
        ]);
        return $cronMinMultiplexor('');
    }

}
