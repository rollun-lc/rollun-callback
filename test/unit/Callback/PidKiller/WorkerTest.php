<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback\PidKiller;

use PHPUnit\Framework\TestCase;
use rollun\callback\PidKiller\Worker;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\callback\Queues\QueueClient;

class WorkerTest extends TestCase
{
    public function testSerializeSuccess()
    {
        $queue = new QueueClient(new FileAdapter('/tmp/test'), 'test');
        $callback = function () {};

        $worker = new Worker($queue, $callback, null);
        $this->assertTrue(boolval(unserialize(serialize($worker))));
    }
}
