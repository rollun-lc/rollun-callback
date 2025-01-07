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
    public function provider(): array
    {
        return [
            [
                'tickerCallback' => function ($val) {
                    file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                },
                'ticksCount' => 10,
                'tickDuration' => 1,
                'delayMicroSecond' => 0,
                'resultFilePath' => 'data/ticker_result_0.txt',
            ],
            [
                'tickerCallback' => function ($val) {
                    file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                },
                'ticksCount' => 5,
                'tickDuration' => 2,
                'delayMicroSecond' => 0,
                'resultFilePath' => 'data/ticker_result_1.txt',
            ],
            [
                'tickerCallback' => function ($val) {
                    file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                },
                'ticksCount' => 2,
                'tickDuration' => 1,
                'delayMicroSecond' => 3,
                'resultFilePath' => 'data/ticker_result_2.txt',
            ],
            [
                'tickerCallback' => function ($val) {
                    file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                },
                'ticksCount' => 2,
                'tickDuration' => 3,
                'delayMicroSecond' => 2,
                'resultFilePath' => 'data/ticker_result_3.txt',
            ],
            [
                'tickerCallback' => new Process(
                    function ($val) {
                        file_put_contents($val, microtime(true) . "\n", FILE_APPEND);
                    }
                ),
                'ticksCount' => 2,
                'tickDuration' => 3,
                'delayMicroSecond' => 2,
                'resultFilePath' => 'data/ticker_result_4.txt',
            ]
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testInvokable(
        callable $tickerCallback, int $ticksCount, int $tickDuration, int $delayMicroSecond, string $resultFilePath
    )
    {
        if (file_exists($resultFilePath)) {
            unlink($resultFilePath);
        }

        $sleepTime = ($ticksCount * $tickDuration) + ($delayMicroSecond / 1000000);
        $object = new Ticker($tickerCallback, $ticksCount, $tickDuration, $delayMicroSecond);

        $startTime = microtime(true);
        $object($resultFilePath);
        $finishTime = microtime(true);

        $workTimeDiff = abs(($finishTime - $startTime) - $sleepTime);
        $this->assertTrue($workTimeDiff >= 0 && $workTimeDiff <= 0.03);

        $data = array_diff(explode("\n", file_get_contents($resultFilePath)), [""]);
        $this->assertTrue(count($data) == $ticksCount);

        for ($i = 1; $i < count($data); $i++) {
            $prevTime = (float)$data[$i - 1];
            $currTime = (float)$data[$i];
            $diff = abs(($currTime - $prevTime) - $tickDuration);
            $this->assertGreaterThanOrEqual(0, $diff);
            $this->assertLessThanOrEqual(0.05, $diff);
        }

        if (file_exists($resultFilePath)) {
            unlink($resultFilePath);
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
        $ticker = new Ticker(new Process(function ($value) {
            return $value;
        }), 4, 1);

        $this->assertTrue($ticker(5) instanceof PayloadInterface);
    }
}
