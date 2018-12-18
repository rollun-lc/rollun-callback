<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\callback\Queues;

use PHPUnit\Framework\TestCase;
use rollun\callback\Queues\FileQueue;
use rollun\callback\Queues\QueueInterface;

class QueueTest extends TestCase
{
    /**
     * @var QueueInterface
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new FileQueue('test_queue');
        $this->object->purgeQueue('test_queue');
    }

    public function testGetNullMessage()
    {
        $message = $this->object->getMessage();
        $this->assertEquals(null, $message);
    }

    public function testAddMessage()
    {
        $this->object->addMessage('test1');
        $message = $this->object->getMessage();
        $this->assertEquals('test1', $message->getData());
    }
}
