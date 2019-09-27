<?php


namespace QueueClientTest\Adapter;


use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zend\Db\Adapter\Adapter;
use rollun\callback\Queues\Adapter\DbAdapter;
use ReputationVIP\QueueClient\Adapter\Exception\InvalidMessageException;
use ReputationVIP\QueueClient\Adapter\Exception\QueueAccessException;
use ReputationVIP\QueueClient\PriorityHandler\ThreeLevelPriorityHandler;
use ReputationVIP\QueueClient\QueueClient;

class DbAdapterTest extends TestCase
{
    /**
     * @var Adapter
     */
    protected $db;

    protected function createObject($timeInFlight): DbAdapter
    {
        return new DbAdapter($this->getDb(), $timeInFlight);
    }

    protected function getDb(): Adapter
    {
        if ($this->db === null) {
            $container = require 'config/container.php';
            $this->db = $container->get('db');
        }
        return $this->db;
    }


    public function getQueueClient(): QueueClient
    {
        $db = $this->getDb();
        return new QueueClient(new DbAdapter($db, 0));
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $object = $this->createObject(0);
        foreach ($object->listQueues() as $queue) {
            $object->deleteQueue($queue);
        }
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        $object = $this->createObject(0);
        foreach ($object->listQueues() as $queue) {
            $object->deleteQueue($queue);
        }
    }

    public function testCreateQueue()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $this->assertTrue(in_array('a', $object->listQueues()));
    }

    public function testMajor()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');

        $object->addMessage('a', 'a');
        $object->addMessage('a','b');
        $object->addMessage('a','c');
        $object->addMessage('a','d');

        $this->assertFalse($object->isEmpty('a'));

        $messages = $object->getMessages('a', 1);
        $this->assertEquals($messages[0]['Body'], 'a');
        $messages = $object->getMessages('a', 1);
        $this->assertEquals($messages[0]['Body'], 'b');
        $messages = $object->getMessages('a', 1);
        $this->assertEquals($messages[0]['Body'], 'c');
        $messages = $object->getMessages('a', 1);
        $this->assertEquals($messages[0]['Body'], 'd');

        $this->assertFalse($object->isEmpty('a'));

        $object->addMessage('a', 'a');
        $object->addMessage('a', 'b');

        $object->purgeQueue('a');
        $this->assertTrue($object->isEmpty('a'));
    }


    public function testCreateQueueWithBadSymbols()
    {
        $object = $this->createObject(5);
        $object->createQueue('â€š"Ã¾');
        $this->assertTrue(true);
    }

    public function testRenameQueueWithBadSymbols()
    {
        $object = $this->createObject(5);
        $object->createQueue('â€š"Ã¾');
        $object->renameQueue('â€š"Ã¾', 'Ã¾â€š"');
        $this->assertTrue(true);
    }

    public function testCreteSameQueueFailed()
    {
        $this->expectException(\Exception::class);
        $object = $this->createObject(5);
        $object->createQueue('5a');
        $this->assertTrue(in_array('5a', $object->listQueues()));

        $object = $this->createObject(5);
        $this->expectException(QueueAccessException::class);
        $object->createQueue('5a');
    }

    public function testDelaySeconds()
    {
        $object = $this->createObject(10);
        $object->createQueue('a');
        $object->addMessage('a', 'message', null, 2);

        $this->assertTrue(empty($object->getMessages('a')));
        sleep(2);
        $this->assertTrue(!empty($object->getMessages('a')));
    }

    public function testTimeInFlight()
    {
        $object = $this->createObject(2);
        $object->createQueue('a');
        $object->addMessage('a', 'message');

        $this->assertTrue(!empty($object->getMessages('a')));
        $this->assertTrue(empty($object->getMessages('a')));
        sleep(3);
        $this->assertTrue(!empty($object->getMessages('a')));
    }

    public function testTimeInFlightWithDelete()
    {
        $object = $this->createObject(2);
        $object->createQueue('a');
        $object->addMessage('a', 'message');

        $message = $object->getMessages('a');
        $object->deleteMessage('a', $message[0]);
        sleep(3);
        $this->assertTrue(empty($object->getMessages('a')));
    }

    public function testGetMessageAndDelete()
    {
        $object = $this->createObject(null);
        $object->createQueue('a');
        $object->addMessage('a', 'message');
        $message = $object->getMessages('a');
        $object->deleteMessage('a', $message[0]);
        $this->assertTrue($object->isEmpty('a'));
    }

    public function testGetMessageAndNotDelete()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $object->addMessage('a', 'message');
        $object->getMessages('a');
        $this->assertTrue(!$object->isEmpty('a'));
        $this->assertTrue(empty($object->getMessages('a')));
    }

    public function testCreateQueueWithSpace()
    {
        $object = $this->createObject(5);
        $this->expectException(InvalidArgumentException::class);
        $object->createQueue('test Queue One');
    }
    
    public function testRenameQueue()
    {
        $object = $this->createObject(5);
        $object->createQueue('testQueue');
        $object->renameQueue('testQueue', 'testRenameQueue');
        $this->assertTrue(true);
    }

    public function testPurgeQueue()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $object->purgeQueue('testQueue');
        $this->assertEmpty($object->getMessages('testQueue'));
    }
    
    public function testAddMessage()
    {
        $object = $this->createObject(10);

        $object->createQueue('testQueue');
        $object->addMessage('testQueue', 'testMessage');
        $this->assertSame($object, $object->addMessage('testQueue', 'testMessage')); //isTestedInstance()
    }

    public function testAddMessages()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $this->assertSame($object, $object->addMessages('testQueue', ['testMessageOne', 'testMessageTwo', 'testMessageThree'])); //>isTestedInstance();
    }

    public function testGetMessages()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $this->assertIsArray($object->getMessages('testQueue'));
    }

    public function testFileAdapterDeleteMessageWithEmptyQueueName()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $this->expectException(InvalidArgumentException::class);
        $object->deleteMessage('', []);
    }

    public function testFileAdapterDeleteMessageWithNoQueueFile()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $priorityHandler = new ThreeLevelPriorityHandler();
        $this->assertSame($object, $object->deleteMessage('testQueue', ['id' => 'testQueue-HIGH559f77704e87c5.40358915', 'priority' => $priorityHandler->getHighest()->getLevel()]));
    }

    public function testFileAdapterDeleteMessageWithNoMessage()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $this->expectException(InvalidMessageException::class);
        $object->deleteMessage('testQueue', []);
    }

    public function testFileAdapterDeleteMessageWithNoIdField()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $priorityHandler = new ThreeLevelPriorityHandler();
        $this->expectException(InvalidMessageException::class);
        $object->deleteMessage('testQueue', ['priority' => $priorityHandler->getHighest()->getLevel()]);
    }

    public function testFileAdapterDeleteMessageWithNotPriorityField()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $this->expectException(InvalidMessageException::class);
        $object->deleteMessage('testQueue', ['id' => 'testQueue-HIGH559f77704e87c5.40358915']);

    }

    public function testFileAdapterDeleteMessageWithBadMessageType()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $this->expectException(InvalidMessageException::class);
        $object->deleteMessage('testQueue', 'message');
    }

    public function testIsEmpty()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $this->assertTrue($object->isEmpty('testQueue'));
    }

    public function testNumberMessage()
    {
        $object = $this->createObject(10);
        $object->createQueue('testQueue');
        $this->assertSame(0, $object->getNumberMessages('testQueue'));
    }

    public function testListQueue()
    {
        $object = $this->createObject(10);

        $object->createQueue('testQueue');
        $object->createQueue('testRegexQueue');
        $object->createQueue('testQueueOne');
        $object->createQueue('testRegexQueueTwo');
        $object->createQueue('testQueueTwo');
        $diff = array_diff(['testQueue', 'testRegexQueue', 'testQueueOne', 'testRegexQueueTwo', 'testQueueTwo'], $object->listQueues());
        $this->assertEmpty($diff);
        $diff = array_diff(['testRegexQueue', 'testRegexQueueTwo'], $object->listQueues('testRegex'));
        $this->assertEmpty($diff);

    }

    public function testGetPriorityHandler()
    {
        $object = $this->createObject(10);
        $this->assertInstanceOf('ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface', $object->getPriorityHandler());
    }
}
