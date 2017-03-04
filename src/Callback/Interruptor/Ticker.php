<?php

/**
 * Created by PhpStorm.
 * User: root
 * Date: 20.01.17
 * Time: 12:09
 */


namespace rollun\callback\Callback\Interruptor;

use rollun\dic\InsideConstruct;
use rollun\promise\Promise\Exception;
use rollun\utils\Time\UtcTime;

/**
 * Ticker
 *
 * @category   callback
 * @package    rollun
 */
class Ticker implements InterruptorInterface
{
    const DEFAULT_INTERRUPTOR_CLASS = Process::class;

    /**
     * @var InterruptorInterface $tickerCallback
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
    protected $delayMS;

    /**
     * Ticker constructor.
     * @param callable|InterruptorInterface $tickerCallback
     * @param int $ticksCount
     * @param int $tickDuration
     * @param int $delayMC
     */
    public function __construct(callable $tickerCallback, $ticksCount = 60, $tickDuration = 1, $delayMC = 0)
    {
        $this->tickerCallback = $tickerCallback;
        $this->ticksCount = $ticksCount;
        $this->tickDuration = $tickDuration;
        $this->delayMS = $delayMC;
    }

    /**
     * @param $value
     * @return array
     */
    protected function tick($value)
    {
        try {
            $result[] = call_user_func($this->tickerCallback, $value);
        } catch (Exception $exception) {
            $result[] = $exception->getMessage();
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
        $function = function($value) {
            $result = [];
            for ($index = 0; $index < $this->ticksCount; $index++) {
                $startTime = UtcTime::getUtcTimestamp(UtcTime::WITH_HUNDREDTHS);
                $result[$startTime]['data'] = $this->tick($value);
                $sleepTime = $startTime + $this->tickDuration - UtcTime::getUtcTimestamp(UtcTime::WITH_HUNDREDTHS);
                $sleepTime = $sleepTime <= 0 ? 0 : $sleepTime;
                usleep($sleepTime * 1000000);
            }
            return $result;
        };
        $class = static::DEFAULT_INTERRUPTOR_CLASS;
        $call = new $class($function->bindTo($this));
        return $call($value);
    }
}
