<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Functional\Callback\PidKiller;

use PHPUnit\Framework\TestCase;
use rollun\callback\Callback\Interrupter\QueueFiller;
use rollun\callback\PidKiller\Worker;
use rollun\callback\PidKiller\WriterInterface;
use rollun\callback\Queues\Adapter\FileAdapter;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueClient;
use rollun\utils\Json\Exception;

class WorkerTest extends TestCase
{
    public function testInvokeSuccess()
    {
        $queue = new QueueClient(new FileAdapter('/tmp/test'), 'queue');
        $callback = function ($value) {
            return $value;
        };
        $worker = new Worker($queue, $callback, null);

        $queue->addMessage(Message::createInstance(QueueFiller::serializeMessage(101)));
        $result = $worker();
        $this->assertEquals(101, $result);
        $this->assertTrue($queue->isEmpty());
    }

    public function testInvokeFailed()
    {
        $queue = new QueueClient(new FileAdapter('/tmp/test'), 'queue');
        $exception = new Exception();
        $callback = function ($value) use ($exception) {
            throw $exception;
        };
        $worker = new Worker($queue, $callback, null);

        $queue->addMessage(Message::createInstance(QueueFiller::serializeMessage(101)));
        $result = $worker();
        $this->assertEquals($result['exception']->getPrevious(), $exception);
        $this->assertTrue($queue->isEmpty());
    }

    public function testInvokeFailedWithTimeInFlight()
    {
        $queue = new QueueClient(new FileAdapter('/tmp/test', 1), 'queue');
        $exception = new Exception();
        $callback = function ($value) use ($exception) {
            throw $exception;
        };
        $worker = new Worker($queue, $callback, null);

        $queue->addMessage(Message::createInstance(QueueFiller::serializeMessage(101)));
        $result = $worker();
        $this->assertEquals($result['exception']->getPrevious(), $exception);
        $this->assertFalse($queue->isEmpty());
    }

    public function testInvokeWithWriter()
    {
        $writerMock = $this->getMockBuilder(WriterInterface::class)->disableOriginalConstructor()->getMock();
        $writerMock->expects($this->once())->method('write')->with((array)101);
        $queue = new QueueClient(new FileAdapter('/tmp/test', 1), 'queue');
        $callback = function ($value) {
            return $value;
        };
        $worker = new Worker($queue, $callback, $writerMock);

        $queue->addMessage(Message::createInstance(QueueFiller::serializeMessage(101)));
        $result = $worker();
        $this->assertEquals(101, $result);
    }
}
