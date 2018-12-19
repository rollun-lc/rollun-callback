<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\Callback;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Example\CallMe;
use rollun\callback\Callback\Interrupter\Process;

/**
 * Class CallbackTestDataProvider
 * @package rollun\test\Callback
 */
class CallbackTestDataProvider extends TestCase
{
    public function providerMainType()
    {
        $stdObject = (object)['prop' => 'Hello '];

        //function
        return array(
            [
                'class_parents',
                self::class,
                [
                    'PHPUnit\Framework\TestCase' => "PHPUnit\Framework\TestCase",
                    'PHPUnit\Framework\Assert' => "PHPUnit\Framework\Assert"
                ]
            ],
            //closure
            [
                function ($val) {
                    return 'Hello ' . $val;
                },
                'World',
                'Hello World'
            ],
            //closure with uses
            [
                function ($val) use ($stdObject) {
                    return $stdObject->prop . $val;
                },
                'World',
                'Hello World'
            ],
            //invokable object
            [
                new CallMe(),
                'World',
                'Hello World'
            ],
            //method
            [
                [new CallMe(), 'method'],
                'World',
                'Hello World'
            ],
            //static method
            [
                [new CallMe(), 'staticMethod'],
                'World',
                'Hello World'
            ],
            [
                [CallMe::class, 'staticMethod'],
                'World',
                'Hello World'
            ],
            [
                '\\' . CallMe::class . '::staticMethod',
                'World',
                'Hello World'
            ],
        );
    }

    public function providerMultiplexerType()
    {
        $stdObject = (object)['prop' => 'Hello '];
        //function
        return array(
            [
                [
                    new Process(function ($val) {
                        return 'Hello ' . $val;
                    }),
                    new Process(function ($val) use ($stdObject) {
                        return $stdObject->prop . $val;
                    }),
                    new Process(new CallMe()),
                    new Process([new CallMe(), 'method']),
                    new Process([new CallMe(), 'staticMethod']),
                    new Process([CallMe::class, 'staticMethod']),
                    new Process('\\' . CallMe::class . '::staticMethod')
                ],
                "World"
            ],
            [
                [
                    new Process(function ($val) {
                        return 'Hello ' . $val;
                    }),
                    new Process(function ($val) use ($stdObject) {
                        throw new \Exception("some error");
                    }),
                    new Process(new CallMe()),
                    new Process([new CallMe(), 'method']),
                    new Process('\\' . CallMe::class . '::staticMethod')
                ],
                "World"
            ],
        );
    }

    public function providerTickerType()
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
            ]

        ];
    }
}
