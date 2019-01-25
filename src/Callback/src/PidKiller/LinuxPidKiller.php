<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\PidKiller;

use DateTime;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use rollun\callback\Queues\Message;
use rollun\callback\Queues\QueueInterface;
use rollun\dic\InsideConstruct;
use RuntimeException;

class LinuxPidKiller
{
    const DEF_MAX_MESSAGE_COUNT = 1000;

    /** @var int */
    protected $maxMessageCount;

    /** @var QueueInterface */
    protected $queue;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        QueueInterface $queue,
        $maxMessageCount = null,
        LoggerInterface $logger = null
    ) {
        if (substr(php_uname(), 0, 7) == "Windows") {
            throw new RuntimeException('Pid killer does not work on Windows');
        }

        if ($maxMessageCount == null) {
            $maxMessageCount = self::DEF_MAX_MESSAGE_COUNT;
        }

        $this->queue = $queue;
        $this->maxMessageCount = $maxMessageCount;
        InsideConstruct::setConstructParams(["logger" => LoggerInterface::class]);
    }

    /**
     * Push new message to queue
     * record array:
     * [
     *  'id' - process pid,
     *  'delaySecond' - delay seconds to become message visible in queue
     * ]
     *
     * @param $record
     */
    public function create($record)
    {
        if (!isset($record['pid'])) {
            throw new InvalidArgumentException("Field 'pid' is missing");
        }

        if (!isset($record['delaySeconds'])) {
            throw new InvalidArgumentException("Field 'delaySeconds' is missing");
        }

        if (!isset($record['lstart'])) {
            throw new InvalidArgumentException("Field 'lstart' is missing");
        }

        $this->queue->addMessage(Message::createInstance([
            'pid' => intval($record['pid']),
            'lstart' => $record['lstart'],
            QueueClient::KEY_DELAY_SECOND => $record['delaySeconds'],
        ]));

        $this->logger->debug("PID-KILLER add pid {pid} to queue at {date}", [
            'date' => date('D d.m H:i:s'),
            'pid' => $record['pid'],
            'lstart' => $record['lstart'],
        ]);
    }

    public function __invoke()
    {
        $this->logger->debug("PID-KILLER start working at {date}", [
            'date' => date('D d.m H:i:s'),
        ]);

        $messageCount = 0;

        while ($messageCount < $this->maxMessageCount && $queueMessage = $this->queue->getMessage()) {
            $messageCount++;
            $message = $queueMessage->getData();
            $pids = self::ps();

            $this->logger->debug("PID-KILLER get message from queue", [
                'message' => $message,
            ]);

            foreach ($pids as $processInfo) {
                if ($processInfo['pid'] == $message['pid']) {
                    $result = exec("kill -9 {$message['pid']}");

                    if ($result) {
                        $this->logger->warning("PID-KILLER failed kill process message from queue", [
                            'message' => $message,
                            'result' => $result,
                        ]);
                    } else {
                        $this->queue->deleteMessage($queueMessage);
                        $this->logger->debug("PID-KILLER successfully kill process and delete message from queue", [
                            'message' => $message,
                        ]);
                    }
                }
            }
        }

        $this->logger->debug("PID-KILLER finish working at {date}", [
            'date' => date('D d.m H:i:s'),
        ]);
    }

    /**
     * Return result of linux ps command
     *
     * Result example
     *  [
     *      0 => [
     *          'pid' => 1,
     *          'lstart' => 123434123,
     *      ],
     *  ]
     *
     * @return array
     */
    public static function ps()
    {
        exec('ps -eo pid,lstart', $pidsInfo);
        array_shift($pidsInfo);
        $pids = [];

        foreach ($pidsInfo as $pidInfo) {
            $pidInfo = trim($pidInfo);
            preg_match('/(?<pid>\d+)\s+(?<lstart>.+)/', $pidInfo, $matches);
            $timestamp = DateTime::createFromFormat('D M d H:i:s Y', $matches['lstart'])->getTimestamp();
            $pids[] = [
                'pid' => intval($matches['pid']),
                'lstart' => $timestamp,
            ];
        }

        return $pids;
    }

    public static function createIdFromPidAndTimestamp($pid, $timestamp = null)
    {
        $timestamp = $timestamp ?? time();

        return "{$pid}.{$timestamp}";
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ["queue"];
    }

    /**
     * @throws \ReflectionException
     */
    public function __wakeup()
    {
        InsideConstruct::initWakeup(["logger" => LoggerInterface::class]);
    }
}
