<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
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
     * Ticker constructor.
     * @param callable $tickerCallback
     * @param int $ticksCount
     * @param int $tickDuration
     * @param int $delayMicroSecond
     */
    public function __construct(callable $tickerCallback, protected $ticksCount = 60, protected $tickDuration = 1, protected $delayMicroSecond = 0)
    {
        if (!$tickerCallback instanceof SerializedCallback) {
            $tickerCallback = new SerializedCallback($tickerCallback);
        }

        $this->tickerCallback = $tickerCallback;
    }

    /**
     * @param $value
     * @return array<int,mixed>|PayloadInterface
     */
    public function __invoke($value = null): array|PayloadInterface
    {
        usleep($this->delayMicroSecond);
        $result = [];
        $interrupterWasCalled = false;

        for ($index = 0; $index < $this->ticksCount; $index++) {
            $startTime = UtcTime::getUtcTimestamp(UtcTime::WITH_HUNDREDTHS);
            $startTimeKey = intval($startTime);

            try {
                $payload = call_user_func($this->tickerCallback, $value);

                if ($payload instanceof PayloadInterface) {
                    $interrupterWasCalled = true;
                    $payload = $payload->getPayload();
                }

                $result[$startTimeKey] = $payload;
            } catch (RuntimeException $exception) {
                $result[$startTimeKey] = $exception->getMessage();
            }

            $sleepTime = $startTime + $this->tickDuration - UtcTime::getUtcTimestamp(UtcTime::WITH_HUNDREDTHS);
            $sleepTime = $sleepTime <= 0 ? 0 : $sleepTime;
            usleep(intval($sleepTime * 1000000));
        }

        if ($interrupterWasCalled) {
            $result = new SimplePayload(null, $result);
        }

        return $result;
    }
}
