<?php


namespace Rollun\Test\Unit\Callback\Queues\Adapter;


use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Laminas\Db\Adapter\Adapter;
use rollun\callback\Queues\Adapter\DbAdapter;
use ReputationVIP\QueueClient\Adapter\Exception\InvalidMessageException;
use ReputationVIP\QueueClient\Adapter\Exception\QueueAccessException;
use ReputationVIP\QueueClient\PriorityHandler\ThreeLevelPriorityHandler;
use Laminas\Db\Metadata\Source\Factory;
use Laminas\Db\Sql\Ddl\DropTable;
use Laminas\Db\Sql\Sql;

class DbAdapterTest extends TestCase
{
    /**
     * @var Adapter
     */
    protected $db;

    protected function createObject($timeInFlight, $maxReceiveCount = 0): DbAdapter
    {
        return new DbAdapter($this->getDb(), $timeInFlight, $maxReceiveCount);
    }

    protected function getDb(): Adapter
    {
        if (getenv("DB_USER") === false) {
            $this->markTestIncomplete('Needs DB for running');
        }
        if ($this->db === null) {
            $container = require 'config/container.php';
            $this->db = $container->get('db');
        }
        return $this->db;
    }

    protected function dropAllTables() {
        $metadata = Factory::createSourceFromAdapter($this->getDb());
        foreach ($metadata->getTableNames() as $tableName) {
            if(!$this->startsWith($tableName, DbAdapter::TABLE_NAME_PREFIX)) {
                continue;
            }
            $table = new DropTable($tableName);
            $sql = new Sql($this->db);
            $this->db->query(
                $sql->buildSqlString($table),
                Adapter::QUERY_MODE_EXECUTE
            );
        }
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->dropAllTables();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->dropAllTables();
    }

    public function testSerialize()
    {
        $object = $this->createObject(0, 1);

        $serializedObject = serialize($object);
        $object = unserialize($serializedObject);
        $object->createQueue('a');
        $object->addMessage('a', 'message1');
        $object->addMessage('a', 'message2');
        $object->addMessage('a', 'message3');
        $object->addMessage('a', 'message');
        $object->getMessages('a', 3);
        sleep(1);
        $this->assertFalse($object->isEmpty('a'));

        $count = $object->getNumberDeadMessages('a');
        $deadMessages = $object->getDeadMessages('a', 10);

        $this->assertEquals(3, $count);
        $this->assertCount(3, $deadMessages);
    }

        public function testDeadMessages()
    {
        $object = $this->createObject(0, 1);
        $object->createQueue('a');
        $object->addMessage('a', 'message1');
        $object->addMessage('a', 'message2');
        $object->addMessage('a', 'message3');
        $object->addMessage('a', 'message');
        $object->getMessages('a', 3);
        sleep(1);
        $this->assertFalse($object->isEmpty('a'));

        $count =  $object->getNumberDeadMessages('a');
        $deadMessages =  $object->getDeadMessages('a', 10);

        $this->assertEquals(3, $count);
        $this->assertCount(3, $deadMessages);
        $this->assertEmpty($object->getDeadMessages('a'));
    }

    public function testDeleteDeadMessages()
    {
        $object = $this->createObject(0, 1);
        $object->createQueue('a');
        $object->addMessage('a', 'message1');
        $object->addMessage('a', 'message2');
        $object->addMessage('a', 'message3');
        $object->addMessage('a', 'message');
        $object->getMessages('a', 3);
        sleep(1);

        $count =  $object->getNumberDeadMessages('a');
        $this->assertEquals(3, $count);
        $object->deleteDeadMessages('a');
        $count =  $object->getNumberDeadMessages('a');
        $this->assertEquals(2, $count);
        $object->deleteDeadMessages('a');
        $count =  $object->getNumberDeadMessages('a');
        $this->assertEquals(1, $count);
        $object->deleteDeadMessages('a');
        $count =  $object->getNumberDeadMessages('a');
        $this->assertEquals(0, $count);


        $this->assertEquals(1, $object->getNumberMessages('a'));
        $this->assertTrue(true);
    }

    public function testOverflowMaxReceiveCounter()
    {
        $object = $this->createObject(0, 3);
        $object->createQueue('a');
        $object->addMessage('a', 'message');
        $object->getMessages('a');
        sleep(1);
        $this->assertFalse($object->isEmpty('a'));

        $object->getMessages('a');
        sleep(1);
        $this->assertFalse($object->isEmpty('a'));

        $object->getMessages('a');
        sleep(1);
        $this->assertTrue($object->isEmpty('a'));
    }

    public function testCreateQueues()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $this->assertTrue(in_array('a', $object->listQueues()));
    }

    public function testCreateQueueWithLongName()
    {
        $object = $this->createObject(5);
        $queueName = str_repeat('abracadabra', 100);
        $this->expectException(InvalidArgumentException::class);
        $object->createQueue($queueName);
    }

    public function testCreateQueueWithLongNameNonAscii()
    {
        $object = $this->createObject(5);
        $queueName = str_repeat('Ã', 33);
        $this->expectException(InvalidArgumentException::class);
        $object->createQueue($queueName);
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

    public function testCreateQueueWithNameStarsWithNumber()
    {
        $object = $this->createObject(5);
        $object->createQueue('555a');
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
        $object = $this->createObject(0);
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

    public function testIsEmptyWithNoMessages()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $this->assertTrue($object->isEmpty('a'));
    }

    public function testIsEmptyWithMessages()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $object->addMessage('a', 'message');
        $this->assertFalse($object->isEmpty('a'));
    }

    public function testIsEmptyWithMessagesInFlight()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $object->addMessage('a', 'message');
        $object->getMessages('a');
        $this->assertFalse($object->isEmpty('a'));
    }

    public function testIsEmptyWithDelayedMessages()
    {
        $object = $this->createObject(5);
        $object->createQueue('a');
        $object->addMessage('a', 'message', null, 5);
        $this->assertFalse($object->isEmpty('a'));
    }


    public function testGetPriorityHandler()
    {
        $object = $this->createObject(10);
        $this->assertInstanceOf('ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface', $object->getPriorityHandler());
    }
}
