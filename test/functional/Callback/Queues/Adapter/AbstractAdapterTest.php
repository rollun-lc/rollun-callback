<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\test\functional\Callback\Queues\Adapter;

use PHPUnit\Framework\TestCase;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface;

abstract class AbstractAdapterTest extends TestCase
{
    protected $queueName;

    /** @var AdapterInterface */
    protected $object;

    abstract protected function createObject($timeInFlight = null): AdapterInterface;

    protected function createQueue($timeInFlight = 0)
    {
        $this->queueName = uniqid();
        $this->object = $this->createObject($timeInFlight);
        $this->object->createQueue($this->queueName);
    }

    protected function deleteQueue()
    {
        $this->object->deleteQueue($this->queueName);
    }

    public function testAddAndGetMessage()
    {
        $this->createQueue();

        $this->object->addMessage($this->queueName, 'a');
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals('a', $messages[0]['Body']);

        $this->deleteQueue();
    }

    public function testDeleteMessage()
    {
        $this->createQueue();
        $this->object->addMessage($this->queueName, 'a');
        $messages = $this->object->getMessages($this->queueName);
        $this->object->deleteMessage($this->queueName, $messages[0]);
        $this->assertTrue($this->object->isEmpty($this->queueName));
        $this->deleteQueue();
    }

    public function testPurgeQueueAndIsEmpty()
    {
        $this->createQueue();

        $this->object->addMessage($this->queueName, 'a');
        $this->object->addMessage($this->queueName, 'b');
        $this->assertFalse($this->object->isEmpty($this->queueName));

        $this->object->purgeQueue($this->queueName);
        $this->assertTrue($this->object->isEmpty($this->queueName));

        $this->deleteQueue();
    }

    public function testListQueues()
    {
        $queueA = uniqid('A');
        $queueB = uniqid('B');
        $queueC = uniqid('C');

        $this->object = $this->createObject();
        $this->object->createQueue($queueA);
        $this->object->createQueue($queueB);

        $startTime = time();
        $maxTime = $startTime + 300;

        do {
            $existQueues = $this->createObject()->listQueues();
            sleep(10);
        } while(!in_array($queueA, $existQueues) && time() < $maxTime);

        $this->assertTrue(in_array($queueA, $existQueues));
        $this->assertTrue(in_array($queueB, $existQueues));
        $this->assertFalse(in_array($queueC, $existQueues));

        $this->object->deleteQueue($queueA);
        $this->object->deleteQueue($queueB);
    }

    public function testRenameQueue()
    {
        $this->object = $this->createObject();

        $queueA = uniqid('hh');
        $queueB = uniqid('ii');

        $this->object->createQueue($queueA);

        $startTime = time();
        $maxTime = $startTime + 300;

        do {
            $existQueues = $this->createObject()->listQueues();
            sleep(10);
        } while(!in_array($queueA, $existQueues) && time() < $maxTime);

        $existQueues = $this->object->listQueues();

        $this->assertTrue(in_array($queueA, $existQueues));
        $this->assertFalse(in_array($queueB, $existQueues));

        $this->object->renameQueue($queueA, $queueB);

        $startTime = time();
        $maxTime = $startTime + 300;

        do {
            $existQueues = $this->createObject()->listQueues();
            sleep(10);
        } while(!in_array($queueB, $existQueues) && time() < $maxTime);

        $this->assertFalse(in_array($queueA, $existQueues));
        $this->assertTrue(in_array($queueB, $existQueues));

        $this->object->deleteQueue($queueB);
    }

    public function testPriorityHandler()
    {
        $this->object = $this->createObject();
        $this->assertTrue($this->object->getPriorityHandler() instanceof PriorityHandlerInterface);
    }

    public function testGetNumberMessages()
    {
        $this->createQueue();

        $this->object->addMessage($this->queueName, 'a');
        $this->object->addMessage($this->queueName, 'b');

        $this->assertEquals($this->object->getNumberMessages($this->queueName), 2);

        $this->object->addMessage($this->queueName, 'c');
        $this->assertEquals($this->object->getNumberMessages($this->queueName), 3);

        $this->deleteQueue();
    }

    public function testDelayWithoutWaiting()
    {
        $this->createQueue();
        $this->object->addMessage($this->queueName, 'a', null, 100);
        $this->assertEquals(0, count($this->object->getMessages($this->queueName)));
        $this->deleteQueue();
    }

    public function testDelayWithWaiting()
    {
        $this->createQueue();

        $this->object->addMessage($this->queueName, 'a', null, 2);
        sleep(3);
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals(1, count($messages));

        $this->deleteQueue();
    }

    public function testTimeInFlightReceiveMessage()
    {
        $this->createQueue(2);

        $this->object->addMessage($this->queueName, 'a');
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals(1, count($messages));

        sleep(3);
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals(1, count($messages));
        $this->object->deleteMessage($this->queueName, $messages[0]);

        sleep(3);
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals(0, count($messages));

        $this->deleteQueue();
    }

    public function testTimeInFlightWithoutSpecifyTime()
    {
        $this->createQueue(2);

        $this->object->addMessage($this->queueName, 'a');
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals(1, count($messages));

        sleep(3);
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals(1, count($messages));

        sleep(3);
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals(1, count($messages));
        $this->object->deleteMessage($this->queueName, $messages[0]);

        sleep(3);
        $messages = $this->object->getMessages($this->queueName);
        $this->assertEquals(0, count($messages));

        $this->deleteQueue();
    }

    public function testSerialize()
    {
        $object = $this->createObject();
        $this->assertTrue((bool)unserialize(serialize($object)));
        $object->createQueue('a');
        $object->deleteQueue('a');
    }
}
