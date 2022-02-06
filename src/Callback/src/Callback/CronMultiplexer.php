<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback;

use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;

class CronMultiplexer extends Multiplexer
{
    private const MAX_EXECUTION_TIME_IN_SEC = 40;

    /**
     * @param $value
     * @return array|PayloadInterface
     */
    public function __invoke($value = null)
    {
        $this->logger->debug("Cron multiplexer started");

        $result = [];
        ksort($this->callbacks);
        $interrupterWasCalled = false;

        $startTime = new \DateTimeImmutable();
        $startTime = $startTime->setTime($startTime->format('H'), $startTime->format('i'), 0, 0);

        $longExecution = false;
        $statistics = [];

        foreach ($this->callbacks as $key => $callback) {
            
            try {
                $start = microtime(true);
                $result[$key] = $callback->runCallback($value);
            } catch (\Throwable $e) {
                $this->logger->error(
                    "Get error '{$e->getMessage()}' by handle '{$key}' callback service.", [
                        'exception' => $e,
                        'multiplexer' => $this->name
                    ]
                );
                $result[$key] = $e;
            }

            $end = microtime(true);
            $statistics[$callback->getName()] = $end - $start;

            if ($result[$key] instanceof PayloadInterface) {
                $result[$key] = $result[$key]->getPayload();
                $interrupterWasCalled = true;
            }

            $now = new \DateTimeImmutable();
            if ($now->getTimestamp() - $startTime->getTimestamp() > self::MAX_EXECUTION_TIME_IN_SEC) {
                $longExecution = true;
            }
        }

        if ($longExecution) {
            $this->logger->critical("Cron works longer than 40 sec", $statistics);
        }

        if ($interrupterWasCalled) {
            $result = new SimplePayload(null, $result);
        }

        return $result;
    }

}
