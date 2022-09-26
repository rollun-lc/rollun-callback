<?php


namespace rollun\callback\Queues\Adapter;

use Exception;
use InvalidArgumentException;
use ReputationVIP\QueueClient\Adapter\AbstractAdapter;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use ReputationVIP\QueueClient\Adapter\Exception\InvalidMessageException;
use ReputationVIP\QueueClient\Adapter\Exception\QueueAccessException;
use ReputationVIP\QueueClient\PriorityHandler\Priority\Priority;
use ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface;
use ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler;
use rollun\dic\InsideConstruct;
use Throwable;
use UnexpectedValueException;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterInterface as DbAdapterInterface;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Metadata\Source\Factory;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Ddl;
use Zend\Db\Sql\Ddl\Column;
use Zend\Db\Sql\Ddl\Constraint;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate\Expression as PredicateExpression;
use Zend\Db\Sql\Predicate\IsNull;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Db\Sql\Sql;


class DbAdapter extends AbstractAdapter implements AdapterInterface, DeadMessagesInterface
{
    const TABLE_NAME_PREFIX = 'queue_';

    const MAX_NB_MESSAGES = 10;
    /** @var DbAdapterInterface $db */
    private $db;
    /** @var int */
    private $timeInFlight;
    /** @var int */
    private $maxReceiveCount;
    /** @var PriorityHandlerInterface $priorityHandler */
    private $priorityHandler;
    /**
     * @var string
     */
    private $_dbAdapterName;

    /**
     * @param Adapter $db
     * @param int $timeInFlight
     * @param int $maxReceiveCount
     * @param PriorityHandlerInterface $priorityHandler
     *
     * @throws QueueAccessException
     */
    public function __construct(
        Adapter                  $db,
        int                      $timeInFlight = 0,
        int                      $maxReceiveCount = 0,
        PriorityHandlerInterface $priorityHandler = null,
        string $_dbAdapterName = 'db'
    ) {
        if (null === $priorityHandler) {
            $priorityHandler = new StandardPriorityHandler();
        }
        $this->db = $db;
        $this->timeInFlight = $timeInFlight;
        $this->maxReceiveCount = $maxReceiveCount;
        $this->priorityHandler = $priorityHandler;
        $this->_dbAdapterName = $_dbAdapterName;
    }

    /**
     * @inheritdoc
     */
    public function listQueues($prefix = '')
    {
        $metadata = Factory::createSourceFromAdapter($this->db);
        $result = [];
        foreach ($metadata->getTableNames() as $tableName) {
            $queueName = $this->dePrepareTableName($tableName);
            if (!empty($prefix) && !$this->startsWith($queueName, $prefix)) {
                continue;
            }
            $result[] = $queueName;
        }
        $result = array_unique($result);
        return $result;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws InvalidMessageException
     * @throws QueueAccessException
     */
    public function addMessage($queueName, $message, Priority $priority = null, $delaySeconds = 0)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (empty($message)) {
            throw new InvalidMessageException($message, 'Message empty or not defined.');
        }

        if (null === $priority) {
            $priority = $this->priorityHandler->getDefault();
        }

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName
                . " doesn't exist, please create it before using it."
            );
        }
        $tableName = $this->prepareTableName($queueName);
        $new_message = [
            'id' => uniqid(
                $queueName . $priority->getLevel(),
                true
            ),
            'priority_level' => $priority->getLevel(),
            'time_in_flight' => null,
            'delayed_until' => time() + $delaySeconds,
            'body' => serialize($message),
            'added_at' => time(),
        ];
        $sql = new Sql($this->db);
        $select = $sql->insert()
            ->into($tableName)
            ->values($new_message);
        $statement = $sql->prepareStatementForSqlObject($select);
        $statement->execute();
        return $this;
    }

    /**
     * @param string $queueName
     *
     * @return bool
     */
    protected function isQueueExists($queueName)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        $tableName = $this->prepareTableName($queueName);
        $metadata = Factory::createSourceFromAdapter($this->db);
        $tableNames = $metadata->getTableNames();
        return in_array($tableName, $tableNames);
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws QueueAccessException
     * @throws Throwable
     */
    public function getMessages($queueName, $nbMsg = 1, Priority $priority = null)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (!is_numeric($nbMsg)) {
            throw new InvalidArgumentException('Number of messages must be numeric.');
        }

        if ($nbMsg <= 0 || $nbMsg > static::MAX_NB_MESSAGES) {
            throw new InvalidArgumentException('Number of messages is not valid.');
        }

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName
                . " doesn't exist, please create it before using it."
            );
        }
        $tableName = $this->prepareTableName($queueName);
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->from($tableName)
            ->where(
                [
                    new PredicateSet(
                        [
                            new PredicateExpression('unix_timestamp(now()) - time_in_flight > ?', $this->timeInFlight),
                            new IsNull('time_in_flight'),
                        ],
                        PredicateSet::COMBINED_BY_OR
                    ),
                    new PredicateExpression('delayed_until <= unix_timestamp(now())'),
                    new PredicateExpression('receive_count < ?', (intval($this->maxReceiveCount) ?: PHP_INT_MAX)),
                ]
            )
            ->order('added_at');
        if (null !== $priority) {
            $select->where(['priority_level' => $priority->getLevel()]);
        }
        if ($nbMsg) {
            $select->limit($nbMsg);
        }

        $sqlString = $sql->buildSqlString($select);
        $statement = $this->db->getDriver()->createStatement($sqlString);
        $results = $statement->execute();
        $messageIds = [];
        if ($results instanceof ResultInterface && $results->isQueryResult()) {
            $resultSet = new ResultSet();
            $resultSet->initialize($results);
            foreach ($resultSet as $result) {
                $messageIds[] = $result->id;
            }
        }

        $messages = [];

        if (empty($messageIds)) {
            return $messages;
        }

        // need to request records specifically by their id, to lock only this records and not all records in the table
        // (previous select will lock all table if requested with FOR UPDATE option)
        $selectByIds = $sql->select()
            ->from($tableName)
            ->where(['id' => $messageIds]);

        $this->db->getDriver()->getConnection()->beginTransaction();
        try {
            // need to use SKIP LOCKED option, to skip messages, that are already used by other process
            $sqlString = $sql->buildSqlString($selectByIds) . ' FOR UPDATE SKIP LOCKED';
            $statement = $this->db->getDriver()->createStatement($sqlString);
            $results = $statement->execute();
            $messageIds = [];
            if ($results instanceof ResultInterface && $results->isQueryResult()) {
                $resultSet = new ResultSet();
                $resultSet->initialize($results);
                foreach ($resultSet as $result) {
                    $messageIds[] = $result->id;
                    $message = [];
                    $message['id'] = $result->id;
                    $message['time-in-flight'] = time();
                    $message['delayed-until'] = intval($result->delayed_until);
                    $message['Body'] = unserialize($result->body);
                    $message['priority'] = intval($result->priority_level);
                    $messages[] = $message;
                }
            }
            if (!empty($messageIds)) {
                $sqlUpdate = $sql->update($tableName)
                    ->set(
                        [
                            'time_in_flight' => time(),
                            'receive_count' => new Expression('receive_count + 1')
                        ]
                    )
                    ->where(['id' => $messageIds]);
                $statement = $sql->prepareStatementForSqlObject($sqlUpdate);
                $statement->execute();

            }

            $this->db->getDriver()->getConnection()->commit();
        } catch (Throwable $e) {
            $this->db->getDriver()->getConnection()->rollback();
            throw $e;
        }


        return $messages;
    }

    /**
     * @inheritdoc
     *
     * @param string $queueName
     * @param array $message
     *
     * @return $this|AdapterInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidMessageException
     * @throws QueueAccessException
     */
    public function deleteMessage($queueName, $message)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (empty($message)) {
            throw new InvalidMessageException($message, 'Message empty or not defined.');
        }

        if (!is_array($message)) {
            throw new InvalidMessageException($message, 'Message must be an array.');
        }

        if (!isset($message['id'])) {
            throw new InvalidMessageException($message, 'Message id not found in message.');
        }

        if (!isset($message['priority'])) {
            throw new InvalidMessageException($message, 'Message priority not found in message.');
        }

        $priority = $this->priorityHandler->getPriorityByLevel($message['priority']);

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName . " doesn't exist, please create it before use it."
            );
        }
        $this->db->getDriver()->getConnection()->beginTransaction();
        try {
            $tableName = $this->prepareTableName($queueName);
            $sql = new Sql($this->db);
            $delete = $sql->delete($tableName)
                ->where(['id' => $message['id']]);
            if (null !== $priority) {
                $delete->where(['priority_level' => $priority->getLevel()]);
            }
            $statement = $sql->prepareStatementForSqlObject($delete);
            $statement->execute();
            $this->db->getDriver()->getConnection()->commit();
        } catch (\Throwable $exception) {
            $this->db->getDriver()->getConnection()->rollback();
            throw $exception;
        }
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws QueueAccessException
     * @throws UnexpectedValueException
     */
    public function isEmpty($queueName, Priority $priority = null)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName
                . " doesn't exist, please create it before using it."
            );
        }
        $tableName = $this->prepareTableName($queueName);
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns(['total' => new Expression('COUNT(*)')])
            ->from($tableName)
            ->where(['receive_count < ?' => (intval($this->maxReceiveCount) ? intval($this->maxReceiveCount) : PHP_INT_MAX)]);
        if (null !== $priority) {
            $select->where(['priority_level' => $priority->getLevel()]);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $count = intval(($results->current())['total']);
        return $count > 0 ? false : true;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws QueueAccessException
     * @throws UnexpectedValueException
     */
    public function getNumberMessages($queueName, Priority $priority = null)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName
                . " doesn't exist, please create it before using it."
            );
        }

        $tableName = $this->prepareTableName($queueName);
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns(['total' => new Expression('COUNT(*)')])
            ->from($tableName)
            ->where(
                [
                    '(unix_timestamp(now()) - time_in_flight) > ?' => $this->timeInFlight,
                    'time_in_flight' => null,
                    'receive_count < ?' => (intval($this->maxReceiveCount) ?: PHP_INT_MAX)
                ],
                Predicate::OP_OR
            );
        if (null !== $priority) {
            $select->where(['priority_level' => $priority->getLevel()]);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $count = intval(($results->current())['total']);
        return $count;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     */
    public function deleteQueue($queueName)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }
        $tableName = $this->prepareTableName($queueName);
        $table = new Ddl\DropTable($tableName);
        $sql = new Sql($this->db);
        $this->db->query(
            $sql->buildSqlString($table),
            Adapter::QUERY_MODE_EXECUTE
        );
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     */
    public function createQueue($queueName)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }
        if (strpos($queueName, ' ') !== false) {
            throw new InvalidArgumentException('Queue name must not contain white spaces.');
        }

        $tableName = $this->prepareTableName($queueName);

        if (strlen($queueName) > 64) {
            throw new InvalidArgumentException('Queue name length must not be grater then 64 symbols.');
        }

        if (false === preg_match("/^[\w]$/", $queueName, $resss)) {
            throw new InvalidArgumentException('Queue name must contain symbols like /^[\w]$/ only.');
        }

        if ($this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                'A queue named ' . $queueName . ' already exist.'
            );
        }
        $table = new Ddl\CreateTable($tableName);
        $table->addColumn(new Column\Varchar('id', 45));
        $table->addColumn(new Column\Integer('priority_level'));
        $table->addColumn(new Column\Blob('body'));
        $table->addColumn(new Column\Integer('time_in_flight', true));
        $table->addColumn(new Column\Integer('delayed_until', true));
        $table->addColumn(new Column\Integer('receive_count', true, 0));
        $table->addColumn(new Column\Integer('added_at'));
        $table->addConstraint(new Constraint\PrimaryKey('id'));
        $sql = new Sql($this->db);
        $this->db->query(
            $sql->buildSqlString($table),
            Adapter::QUERY_MODE_EXECUTE
        );

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     */
    public function renameQueue($sourceQueueName, $targetQueueName)
    {
        if (empty($sourceQueueName)) {
            throw new InvalidArgumentException('Source queue name empty or not defined.');
        }

        if (empty($targetQueueName)) {
            throw new InvalidArgumentException('Target queue name empty or not defined.');
        }

        $platform = $this->db->getPlatform();
        $sourceTableName = $platform->quoteIdentifier($this->prepareTableName($sourceQueueName));
        $targetTableName = $platform->quoteIdentifier($this->prepareTableName($targetQueueName));
        $sql = "ALTER TABLE $sourceTableName RENAME TO $targetTableName;";
        $this->db->query($sql, Adapter::QUERY_MODE_EXECUTE);
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws QueueAccessException
     * @throws UnexpectedValueException
     */
    public function purgeQueue($queueName, Priority $priority = null)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (null === $priority) {
            $priorities = $this->priorityHandler->getAll();
            foreach ($priorities as $priority) {
                $this->purgeQueue($queueName, $priority);
            }

            return $this;
        }

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName
                . " doesn't exist, please create it before using it."
            );
        }
        $tableName = $this->prepareTableName($queueName);
        $sql = new Sql($this->db);
        $select = $sql->delete()
            ->from($tableName);
        if (null !== $priority) {
            $select->where(['priority_level' => $priority->getLevel()]);
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $statement->execute();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPriorityHandler()
    {
        return $this->priorityHandler;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws QueueAccessException
     * @throws UnexpectedValueException
     */
    public function getNumberDeadMessages($queueName): int
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName
                . " doesn't exist, please create it before using it."
            );
        }

        $tableName = $this->prepareTableName($queueName);
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns(['total' => new Expression('COUNT(*)')])
            ->from($tableName)
            ->where(['receive_count >= ?' => (intval($this->maxReceiveCount) ?: PHP_INT_MAX)]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $count = intval(($results->current())['total']);
        return $count;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws QueueAccessException
     * @throws Throwable
     */
    public function getDeadMessages($queueName, $nbMsg = 1): array
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (!is_numeric($nbMsg)) {
            throw new InvalidArgumentException('Number of messages must be numeric.');
        }

        if ($nbMsg <= 0 || $nbMsg > static::MAX_NB_MESSAGES) {
            throw new InvalidArgumentException('Number of messages is not valid.');
        }

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName
                . " doesn't exist, please create it before using it."
            );
        }
        $tableName = $this->prepareTableName($queueName);
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->from($tableName)
            ->where(['receive_count >= ?' => (intval($this->maxReceiveCount) ?: PHP_INT_MAX)])
            ->order('added_at');
        if ($nbMsg) {
            $select->limit($nbMsg);
        }
        $messages = [];
        $this->db->getDriver()->getConnection()->beginTransaction();
        try {
            $sqlString = $sql->buildSqlString($select) . ' FOR UPDATE';
            $statement = $this->db->getDriver()->createStatement($sqlString);
            $results = $statement->execute();
            $messageIds = [];
            if ($results instanceof ResultInterface && $results->isQueryResult()) {
                $resultSet = new ResultSet();
                $resultSet->initialize($results);
                foreach ($resultSet as $result) {
                    $messageIds[] = $result->id;
                    $message = [];
                    $message['id'] = $result->id;
                    $message['time-in-flight'] = time();
                    $message['delayed-until'] = intval($result->delayed_until);
                    $message['Body'] = unserialize($result->body);
                    $message['priority'] = intval($result->priority_level);
                    $messages[] = $message;
                }
            }
            if (!empty($messageIds)) {
                $sqlUpdate = $sql->delete($tableName)
                    ->where(['id' => $messageIds]);
                $statement = $sql->prepareStatementForSqlObject($sqlUpdate);
                $statement->execute();
            }
            $this->db->getDriver()->getConnection()->commit();
        } catch (Throwable $e) {
            $this->db->getDriver()->getConnection()->rollback();
            throw $e;
        }
        return $messages;
    }

    /**
     * @inheritdoc
     *
     * @throws InvalidArgumentException
     * @throws QueueAccessException
     * @throws Throwable
     */
    public function deleteDeadMessages($queueName, $nbMsg = 1)
    {
        if (empty($queueName)) {
            throw new InvalidArgumentException('Queue name empty or not defined.');
        }

        if (!is_numeric($nbMsg)) {
            throw new InvalidArgumentException('Number of messages must be numeric.');
        }

        if ($nbMsg <= 0 || $nbMsg > static::MAX_NB_MESSAGES) {
            throw new InvalidArgumentException('Number of messages is not valid.');
        }

        if (!$this->isQueueExists($queueName)) {
            throw new QueueAccessException(
                "Queue " . $queueName
                . " doesn't exist, please create it before using it."
            );
        }
        $tableName = $this->prepareTableName($queueName);
        $sql = new Sql($this->db);
        $select = $sql->select()
            ->columns(['id'])
            ->from($tableName)
            ->where(['receive_count >= ?' => (intval($this->maxReceiveCount) ?: PHP_INT_MAX)])
            ->order('added_at');
        if ($nbMsg) {
            $select->limit($nbMsg);
        }
        $this->db->getDriver()->getConnection()->beginTransaction();
        try {
            $sqlString = $sql->buildSqlString($select) . ' FOR UPDATE';
            $statement = $this->db->getDriver()->createStatement($sqlString);
            $results = $statement->execute();
            $messageIds = [];
            if ($results instanceof ResultInterface && $results->isQueryResult()) {
                $resultSet = new ResultSet();
                $resultSet->initialize($results);
                foreach ($resultSet as $result) {
                    $messageIds[] = $result->id;
                }
            }
            if (!empty($messageIds)) {
                $sqlUpdate = $sql->delete($tableName)
                    ->where(['id' => $messageIds]);
                $statement = $sql->prepareStatementForSqlObject($sqlUpdate);
                $statement->execute();
            }
            $this->db->getDriver()->getConnection()->commit();
        } catch (Throwable $e) {
            $this->db->getDriver()->getConnection()->rollback();
            throw $e;
        }
        return $this;
    }

    /**
     * @param $haystack
     * @param $needle
     *
     * @return bool
     */
    private function startsWith(string $haystack, string $needle): bool
    {
        return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
     * @param string $queueName
     *
     * @return string
     */
    protected function prepareTableName(string $queueName): string
    {
        return static::TABLE_NAME_PREFIX . $queueName . $this->makeTableNameSuffix();
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function dePrepareTableName(string $tableName): string
    {
        $tableNameWithoutPrefix = substr($tableName, strlen(static::TABLE_NAME_PREFIX));
        $suffix = $this->makeTableNameSuffix();
        $tableNameWithoutSuffix = substr($tableNameWithoutPrefix, 0, -strlen($suffix));
        return $tableNameWithoutSuffix;
    }

    /**
     * @return string
     */
    protected function makeTableNameSuffix(): string
    {
        $suffixParams = [
            $this->timeInFlight,
            $this->maxReceiveCount,
        ];
        return '_' . implode('_', $suffixParams);
    }

    /**
     * @throws Exception
     */
    public function __wakeup()
    {
        try {
            InsideConstruct::initWakeup(
                [
                    "db" => $this->_dbAdapterName,
                ]
            );
        } catch (\Throwable $e) {
            throw new Exception("Can't deserialize itself. Reason: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return [
            '_dbAdapterName',
            'timeInFlight',
            'maxReceiveCount',
            'priorityHandler',
        ];
    }
}
