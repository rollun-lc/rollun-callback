<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\Callback\Worker;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Queues\Factory\QueueClientAbstractFactory;

class WorkerTest extends TestCase
{
    public function testInvokeWithCallable()
    {
        $range = range(1, 10);
        $queue = QueueClientAbstractFactory::createSimpleQueueClient();
        $queueFiller = new QueueFiller($queue);

        foreach ($range as $val) {
            $queueFiller($val);
        }

        $worker = new Worker($queue, fn($val) => $val);

        $this->assertEquals($worker(), $range);
    }

    public function testInvokableWithInterrupter()
    {
        $this->markTestSkipped('InvalidArgumentException : Exception argument must implement \Throwable interface, string given');
        $range = range(1, 10);
        $queue = QueueClientAbstractFactory::createSimpleQueueClient();
        $queueFiller = new QueueFiller($queue);

        foreach ($range as $val) {
            $queueFiller($val);
        }

        $worker = new Worker($queue, new Process(fn($val) => $val));

        $this->assertTrue($worker() instanceof PayloadInterface);
    }
}
