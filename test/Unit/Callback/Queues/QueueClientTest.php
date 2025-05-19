<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Unit\Callback\Queues;

use PHPUnit\Framework\TestCase;
use ReputationVIP\QueueClient\Adapter\MemoryAdapter;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueClient;
use Laminas\ServiceManager\ServiceManager;

class QueueClientTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $container;

    protected function getContainer(): ServiceManager
    {
        if ($this->container === null) {
            $this->container = require 'config/container.php';
        }

        return $this->container;
    }

    protected function createObject(): QueueClient
    {
        return new QueueClient(new MemoryAdapter(), 'testAdapter');
    }

    public function testMajor()
    {
        $object = $this->createObject();

        $object->addMessage(Message::createInstance('a'));
        $object->addMessage(Message::createInstance('b'));
        $object->addMessage(Message::createInstance('c'));
        $object->addMessage(Message::createInstance('d'));

        $this->assertFalse($object->isEmpty());

        $this->assertEquals($object->getMessage()->getData(), 'a');
        $this->assertEquals($object->getMessage()->getData(), 'b');
        $this->assertEquals($object->getMessage()->getData(), 'c');
        $this->assertEquals($object->getMessage()->getData(), 'd');

        //$this->assertTrue($object->isEmpty());
        $this->assertNull($object->getMessage());

        $object->addMessage(Message::createInstance('a'));
        $object->addMessage(Message::createInstance('b'));

        $object->purgeQueue();
        $this->assertTrue($object->isEmpty());
    }

    public function testFactories()
    {
        $this->markTestSkipped('Needs AWS key');
        $this->expectExceptionMessage(
            'Service with name "testSqsQueue" could not be created.'
            . ' Reason: A region is required when using Amazon Simple Queue Service'
        );
        $this->assertTrue($this->getContainer()->get('testSqsQueueClient') instanceof QueueClient);
        $this->assertTrue($this->getContainer()->get('testFileQueueClient') instanceof QueueClient);
        $this->assertTrue($this->getContainer()->get('testDbQueueClient') instanceof QueueClient);
    }
}
