<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback\PidKiller;

use PHPUnit\Framework\TestCase;
use rollun\callback\PidKiller\Worker;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\callback\Queues\QueueClient;

class WorkerTest extends TestCase
{
    public function testSerializeSuccess()
    {
        $queue = new QueueClient(new FileAdapter('/tmp/test'), 'test');
        // What is under test is that Worker survives a round-trip; the callback is only a stub.
        // An invokable object keeps it out of opis/closure, a closure would not.
        $callback = new NoopCallback();

        $worker = new Worker($queue, $callback, null);
        $this->assertTrue(boolval(unserialize(serialize($worker))));
    }
}

class NoopCallback
{
    public function __invoke($value = null): void {}
}
