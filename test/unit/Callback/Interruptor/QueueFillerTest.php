<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\unit\Callback\Interruptor;

use PHPUnit\Framework\TestCase;
use ReputationVIP\QueueClient\Adapter\MemoryAdapter;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueClient;
use rollun\callback\Queues\QueueInterface;

class QueueFillerTest extends TestCase
{
    /**
     * @var QueueInterface
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new QueueClient(new MemoryAdapter(), 'test_queue');
        $this->object->purgeQueue();
    }

    public function testGetNullMessage()
    {
        $message = $this->object->getMessage();
        $this->assertEquals(null, $message);
    }

    public function testAddMessage()
    {
        $this->object->addMessage(Message::createInstance('test1'));
        $message = $this->object->getMessage();
        $this->assertEquals('test1', $message->getData());
    }
}
