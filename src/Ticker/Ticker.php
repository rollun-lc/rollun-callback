<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Ticker;

use rollun\dic\InsideConstruct;
use rollun\utils\Time\UtcTime;

/**
 * Ticker
 *
 * @category   callback
 * @package    zaboy
 */
class Ticker
{

    /**
     *
     * @var $tickerCallback
     */
    protected $tickerCallback;

    /**
     *
     * @var int
     */
    protected $ticksCount;

    /**
     *
     * @var int in seconds
     */
    protected $tickDuration;

    public function __construct(callable $tickerCallback = null, $ticksCount = 60, $tickDuration = 1)
    {
        InsideConstruct::setConstructParams();
    }

    public function secBySec60ticks()
    {
        $result= [];
        for ($index = 0; $index < 60; $index++) {
            $startTime = UtcTime::getUtcTimestamp(5);
            $result[$startTime] = $this->everySec($index) . PHP_EOL . '<br>';
            $sleepTime = $startTime + 1 - UtcTime::getUtcTimestamp(5);
            usleep($sleepTime * 1000000);
            //echo $startTime . '  ';
        }
        return $result;
    }

    public function everySec()
    {
        return UtcTime::getUtcTimestamp(5);
    }

    public function everyMin()
    {
        $result= [];
        return $result;
    }

    public function everyHour()
    {
        $result= [];
        return $result;
    }

}
