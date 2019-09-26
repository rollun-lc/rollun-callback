<?php


namespace QueueClientTest\Adapter;


use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReputationVIP\QueueClient\Adapter\Exception\InvalidMessageException;
use ReputationVIP\QueueClient\Adapter\Exception\QueueAccessException;
use ReputationVIP\QueueClient\Exception\QueueAliasException;
use ReputationVIP\QueueClient\PriorityHandler\ThreeLevelPriorityHandler;
use ReputationVIP\QueueClient\QueueClient;
use Zend\ServiceManager\ServiceManager;

class DbAdapterTest extends TestCase
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



    public function getQueueClient(): QueueClient
    {
        return $this->getContainer()->build('Application\QueueClient');
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        $queueClient = $this->getQueueClient();
        foreach ($queueClient->listQueues() as $queue) {
            $queueClient->deleteQueue($queue);
        }
//        foreach ($queueClient->getAliases() as $alias) {
//            $queueClient->removeAlias($alias);
//        }
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        $queueClient = $this->getQueueClient();
        foreach ($queueClient->listQueues() as $queue) {
            $queueClient->deleteQueue($queue);
        }
    }


    public function testQueueClientCreateQueueWithSpace()
    {
        $queueClient = $this->getQueueClient();
        $this->expectException(InvalidArgumentException::class);
        $queueClient->createQueue('test Queue One');
    }

    public function testQueueClientAddMessageWithAlias()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $queueClient->addAlias('testQueueOne', 'queueAlias');
        $this->assertSame($queueClient, $queueClient->addMessage('queueAlias', 'testMessage'));
        $queueClient->addAlias('testQueueTwo', 'queueAlias');
        $this->assertSame($queueClient, $queueClient->addMessage('queueAlias', 'testMessage'));
    }

    public function testQueueClientAddMessage()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueue');
        $queueClient->addMessage('testQueue', 'testMessage');
        $this->assertSame($queueClient, $queueClient->addMessage('testQueue', 'testMessage')); //isTestedInstance()
    }

    public function testQueueClientAddMessages()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $this->assertSame($queueClient, $queueClient->addMessages('testQueue', ['testMessageOne', 'testMessageTwo', 'testMessageThree'])); //>isTestedInstance();
    }

    public function testQueueClientGetMessagesWithAlias()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $queueClient->addAlias('testQueueOne', 'queueAlias');
        $queueClient->addAlias('testQueueTwo', 'queueAlias');

        $this->expectException(QueueAliasException::class);
        $queueClient->getMessages('queueAlias');
    }

    public function testQueueClientGetMessages()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $this->assertIsArray($queueClient->getMessages('testQueue'));
    }

    public function testQueueClientDeleteMessageWithAlias()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $queueClient->addAlias('testQueueOne', 'queueAlias');
        $queueClient->addAlias('testQueueTwo', 'queueAlias');

        $this->expectException(QueueAliasException::class);
        $queueClient->deleteMessage('queueAlias', ['testMessage']); // exception
    }


    public function testFileAdapterDeleteMessageWithEmptyQueueName()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $this->expectException(InvalidArgumentException::class);
        $queueClient->deleteMessage('', []);
    }

    public function testFileAdapterDeleteMessageWithNoQueueFile()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $priorityHandler = new ThreeLevelPriorityHandler();
        $this->assertSame($queueClient, $queueClient->deleteMessage('testQueue', ['id' => 'testQueue-HIGH559f77704e87c5.40358915', 'priority' => $priorityHandler->getHighest()->getLevel()]));

    }

    public function testFileAdapterDeleteMessageWithNoMessage()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $this->expectException(InvalidMessageException::class);
        $queueClient->deleteMessage('testQueue', []);
    }

    public function testFileAdapterDeleteMessageWithNoIdField()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $priorityHandler = new ThreeLevelPriorityHandler();
        $this->expectException(InvalidMessageException::class);
        $queueClient->deleteMessage('testQueue', ['priority' => $priorityHandler->getHighest()->getLevel()]);
    }

    public function testFileAdapterDeleteMessageWithNotPriorityField()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $this->expectException(InvalidMessageException::class);
        $queueClient->deleteMessage('testQueue', ['id' => 'testQueue-HIGH559f77704e87c5.40358915']);

    }

    public function testFileAdapterDeleteMessageWithBadMessageType()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $this->expectException(InvalidMessageException::class);
        $queueClient->deleteMessage('testQueue', 'message');
    }

    public function testQueueClientIsEmptyWithAlias()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $queueClient->addAlias('testQueueOne', 'queueAlias');
        $queueClient->addAlias('testQueueTwo', 'queueAlias');

        $this->expectException(QueueAliasException::class);
        $queueClient->isEmpty('queueAlias');
    }

    public function testQueueClientIsEmpty()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $this->assertTrue($queueClient->isEmpty('testQueue'));
    }

    public function testQueueClientGetNumberMessageWithAlias()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $queueClient->addAlias('testQueueOne', 'queueAlias');
        $queueClient->addAlias('testQueueTwo', 'queueAlias');

        $this->expectException(QueueAliasException::class);
        $queueClient->getNumberMessages('queueAlias');
    }

    public function testQueueClientNumberMessage()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $this->assertSame(0, $queueClient->getNumberMessages('testQueue'));
    }

    public function testQueueClientDeleteQueueWithAlias()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $queueClient->addAlias('testQueue', 'queueAliasOne');
        $queueClient->addAlias('testQueue', 'queueAliasTwo');
        $this->assertSame($queueClient, $queueClient->deleteQueue('testQueue')); // isTestedInstance();
        $this->assertEmpty($queueClient->getAliases());
    }

    public function testQueueClientDeleteQueue()
    {
        $queueClient = $this->getQueueClient();

        $this->assertSame($queueClient, $queueClient->deleteQueue('testQueue')); //isTestedInstance();
    }

    public function testQueueClientCreateQueue()
    {
        $queueClient = $this->getQueueClient();
        $this->assertSame($queueClient, $queueClient->createQueue('testQueue')); //isTestedInstance();
    }

    public function testQueueClientRenameQueueWithAlias()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueue');
        $queueClient->addAlias('testQueue', 'queueAliasOne');
        $queueClient->addAlias('testQueue', 'queueAliasTwo');
        $this->assertSame($queueClient, $queueClient->renameQueue('testQueue', 'testRenameQueue')); //isTestedInstance();
        $alases = $queueClient->getAliases();
        $this->assertIsArray($alases);
        $this->assertSame(['queueAliasOne' => ['testRenameQueue'], 'queueAliasTwo' => ['testRenameQueue']], $alases);
    }

    public function testQueueClientRenameQueue()
    {
        $queueClient = $this->getQueueClient();

        $this->assertSame($queueClient, $queueClient->renameQueue('testQueue', 'testRenameQueue'));// isTestedInstance();
    }

    public function testQueueClientPurgeQueueWithAlias()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $queueClient->addAlias('testQueueOne', 'queueAlias');
        $queueClient->addAlias('testQueueTwo', 'queueAlias');

        $this->expectException(QueueAliasException::class);
        $queueClient->purgeQueue('queueAlias');
    }

    public function testQueueClientPurgeQueue()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueue');
        $this->assertSame($queueClient, $queueClient->purgeQueue('testQueue')); // isTestedInstance();
    }

    public function testQueueClientListQueue()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueue');
        $queueClient->createQueue('testRegexQueue');
        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testRegexQueueTwo');
        $queueClient->createQueue('testQueueTwo');
        $diff = array_diff(['testQueue', 'testRegexQueue', 'testQueueOne', 'testRegexQueueTwo', 'testQueueTwo'], $queueClient->listQueues());
        $this->assertEmpty($diff);
        $diff = array_diff(['testRegexQueue', 'testRegexQueueTwo'], $queueClient->listQueues('/.*Regex.*/'));
        $this->assertEmpty($diff);
    }

    public function testQueueClientAddAliasWithEmptyAlias()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueue');

        $this->expectException(QueueAliasException::class);
        $queueClient->addAlias('testQueue', '');
    }

    public function testQueueClientAddAliasWithEmptyQueueName()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueue');

        $this->expectException(InvalidArgumentException::class);
        $queueClient->addAlias('', 'queueAlias');
    }

    public function testQueueClientAddAliasOnUndefinedQueue()
    {
        $queueClient = $this->getQueueClient();
        $this->expectException(QueueAccessException::class);
        $queueClient->addAlias('testQueue', 'queueAlias');
    }

    public function testQueueClientAddAlias()
    {
        $queueClient = $this->getQueueClient();
        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $this->assertSame($queueClient, $queueClient->addAlias('testQueueOne', 'queueAlias'));
        $this->assertSame($queueClient, $queueClient->addAlias('testQueueTwo', 'queueAlias'));
        $mockAliases = ['queueAlias' => ['testQueueOne', 'testQueueTwo']];
        $aliases = $queueClient->getAliases();
        $this->assertEquals($mockAliases, $aliases);
    }

    public function testQueueClientRemoveAliasWithUndefinedAlias()
    {
        $queueClient = $this->getQueueClient();

        $this->expectException(QueueAliasException::class);
        $queueClient->RemoveAlias('queueAlias');
    }

    public function testQueueClientRemoveAlias()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $queueClient->addAlias('testQueueOne', 'queueAliasOne');
        $queueClient->addAlias('testQueueTwo', 'queueAliasTwo');
        $this->assertSame($queueClient, $queueClient->removeAlias('queueAliasOne'));
        $this->assertIsArray($queueClient->getAliases());
        $this->assertEquals(['queueAliasTwo' => ['testQueueTwo']], $queueClient->getAliases());

    }

    public function testQueueClientGetAliases()
    {
        $queueClient = $this->getQueueClient();

        $queueClient->createQueue('testQueueOne');
        $queueClient->createQueue('testQueueTwo');
        $queueClient->addAlias('testQueueOne', 'queueAliasOne');
        $queueClient->addAlias('testQueueTwo', 'queueAliasOne');
        $queueClient->addAlias('testQueueTwo', 'queueAliasTwo');
        $this->assertIsArray($queueClient->getAliases());
        $this->assertEquals(['queueAliasOne' => ['testQueueOne', 'testQueueTwo'], 'queueAliasTwo' => ['testQueueTwo']], $queueClient->getAliases());
    }

    public function testQueueClientGetPriorityHandler()
    {
        $queueClient = $this->getQueueClient();
        $this->assertInstanceOf('ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface', $queueClient->getPriorityHandler());
    }

}