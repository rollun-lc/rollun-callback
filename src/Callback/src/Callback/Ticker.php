<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 20.01.17
 * Time: 12:09
 */


namespace rollun\callback\Callback;

use rollun\dic\InsideConstruct;
use rollun\promise\Promise\Exception;
use rollun\utils\Time\UtcTime;

/**
 * Ticker
 *
 * @category   callback
 * @package    rollun
 */
class Ticker implements CallbackInterface
{
    /**
     * @var callable $tickerCallback
     */
    protected $tickerCallback;

    /**
     * @var int
     */
    protected $ticksCount;

    /**
     * @var int in seconds
     */
    protected $tickDuration;

    /** @var  int */
    protected $delayMicroSecond;

    /**
     * Ticker constructor.
     * @param callable $tickerCallback
     * @param int $ticksCount
     * @param int $tickDuration
     * @param int $delayMicroSecond
     */
    public function __construct(callable $tickerCallback, $ticksCount = 60, $tickDuration = 1, $delayMicroSecond = 0)
    {
        $this->tickerCallback = new Callback($tickerCallback);
        $this->ticksCount = $ticksCount;
        $this->tickDuration = $tickDuration;
        $this->delayMicroSecond = $delayMicroSecond;
    }

    /**
     * @param $value
     * @return array
     */
    protected function tick($value)
    {
        usleep($this->delayMicroSecond);
        $result = [];
        for ($index = 0; $index < $this->ticksCount; $index++) {
            $startTime = UtcTime::getUtcTimestamp(UtcTime::WITH_HUNDREDTHS);
            try {
                $result[$startTime]['data'] = call_user_func($this->tickerCallback, $value);
            } catch (Exception $exception) {
                $result[$startTime]['data'] = $exception->getMessage();
            }
            $sleepTime = $startTime + $this->tickDuration - UtcTime::getUtcTimestamp(UtcTime::WITH_HUNDREDTHS);
            $sleepTime = $sleepTime <= 0 ? 0 : $sleepTime;
            usleep($sleepTime * 1000000);
        }
        return $result;
    }

    /**
     * @param $value
     * @return array
     * array contains field
     */
    public function __invoke($value)
    {
        return $this->tick($value);
    }
}
