<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use rollun\utils\Time\UtcTime;
use RuntimeException;

/**
 * Class Ticker
 * @package rollun\callback\Callback
 */
class Ticker
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
        if (!$tickerCallback instanceof SerializedCallback) {
            $tickerCallback = new SerializedCallback($tickerCallback);
        }

        $this->tickerCallback = $tickerCallback;
        $this->ticksCount = $ticksCount;
        $this->tickDuration = $tickDuration;
        $this->delayMicroSecond = $delayMicroSecond;
    }

    /**
     * @param $value
     * @return array
     * array contains field
     */
    public function __invoke($value = null)
    {
        usleep($this->delayMicroSecond);
        $result = [];

        for ($index = 0; $index < $this->ticksCount; $index++) {
            $startTime = UtcTime::getUtcTimestamp(UtcTime::WITH_HUNDREDTHS);

            try {
                $result[$startTime]['data'] = call_user_func($this->tickerCallback, $value);
            } catch (RuntimeException $exception) {
                $result[$startTime]['data'] = $exception->getMessage();
            }

            $sleepTime = $startTime + $this->tickDuration - UtcTime::getUtcTimestamp(UtcTime::WITH_HUNDREDTHS);
            $sleepTime = $sleepTime <= 0 ? 0 : $sleepTime;
            usleep($sleepTime * 1000000);
        }

        return $result;
    }
}
