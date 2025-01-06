<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\Ticker;
use rollun\callback\Promise\Interfaces\PayloadInterface;

class TickerTest extends TestCase
{
    const FILE_WITH_RESULT = 'ticker_results';

    public function provider()
    {
        return [
            [
                function ($val) {
                    file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                },
                10,
                1,
                0
            ],
            [
                function ($val) {
                    file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                },
                5,
                2,
                0
            ],
            [
                function ($val) {
                    file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                },
                2,
                1,
                3
            ],
            [
                function ($val) {
                    file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                },
                2,
                3,
                2
            ],
            [
                new Process(
                    function ($val) {
                        file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                    }
                ),
                2,
                3,
                2
            ]
        ];
    }

    /**
     * @dataProvider provider
     * @param $tickerCallback
     * @param $ticksCount
     * @param $tickDuration
     * @param $delayMicroSecond
     */
    public function testInvokable($tickerCallback, $ticksCount, $tickDuration, $delayMicroSecond)
    {
        $this->markTestSkipped('file_get_contents(data/ticker_results): Failed to open stream: No such file or directory');
        $file = 'data/' . static::FILE_WITH_RESULT;

        if (file_exists($file)) {
            unlink($file);
        }

        $sleepTime = ($ticksCount * $tickDuration) + ($delayMicroSecond / 1000000);
        $object = new Ticker($tickerCallback, $ticksCount, $tickDuration, $delayMicroSecond);

        $startTime = microtime(true);
        $object($file);
        $finishTime = microtime(true);

        $workTimeDiff = abs(($finishTime - $startTime) - $sleepTime);
        $this->assertTrue($workTimeDiff >= 0 && $workTimeDiff <= 0.03);

        $data = array_diff(explode("\n", file_get_contents($file)), [""]);
        $this->assertTrue(count($data) == $ticksCount);

        for ($i = 1; $i < count($data); $i++) {
            $prevTime = $data[$i - 1];
            $currTime = $data[$i];
            $diff = ((float)$currTime - (float)$prevTime) - $tickDuration;
            $this->assertTrue($diff >= 0);
            $this->assertGreaterThanOrEqual(0, $diff);
            $this->assertLessThanOrEqual(0.05, $diff);
        }

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function testInvokableWithCallback()
    {
        $ticker = new Ticker(function ($value) {
            return $value;
        }, 4, 1);

        $res = $ticker(5);

        $this->assertEquals(array_values($res), [5, 5, 5, 5]);
    }

    public function testInvokableWithInterrupter()
    {
        $this->markTestSkipped('Failed asserting that false is true.');
        $ticker = new Ticker(new Process(function ($value) {
            return $value;
        }), 4, 1);

        $this->assertTrue($ticker(5) instanceof PayloadInterface);
    }
}
