<?php


namespace rollun\callback\PidKiller;


use DateTime;
use RuntimeException;

class ProcessManager
{
    /**
     * @param $pid
     * @return string|null
     */
    public function kill($pid): ?string
    {
        return exec("kill -9 {$pid}");
    }

    /**
     * @param $pid
     * @param $lstart
     * @return string
     */
    public function generateId($pid, $lstart): string
    {
        return "{$pid}.{$lstart}";
    }

    /**
     * Return result of linux ps command
     *
     * Result example
     *  [
     *      0 => [
     *          'id' => '1.123434123',
     *          'pid' => 1,
     *          'lstart' => 123434123,
     *      ],
     *  ]
     *
     * @return array
     */
    public function ps(): array
    {
        $options = [
            "pid",
            "lstart"
        ];
        $options[] = match (php_uname('s')) {
            "FreeBSD", "Darwin" => 'command',
            "Linux" => 'cmd',
            default => throw new RuntimeException(sprintf('Unsupported OS %s', php_uname('s'))),
        };
        $cmd = sprintf('ps -eo %s | grep php', implode(',', $options));
        exec($cmd, $pidsInfo);
        array_shift($pidsInfo);
        $pids = [];

        foreach ($pidsInfo as $pidInfo) {
            try {
                $pidInfo = trim($pidInfo);
                preg_match('/^(?<pid>\d+)\s+(?<lstart>\w{3}\s+\w{3}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2}\s+\d{4})/', $pidInfo, $matches);
                $timestamp = DateTime::createFromFormat('D M d H:i:s Y', $matches['lstart'])->getTimestamp();
                $pid = (int)$matches['pid'];
                $pids[] = [
                    'id' => $this->generateId($pid, $timestamp),
                    'pid' => $pid,
                    'lstart' => $timestamp,
                ];
            } catch (\Throwable $exception) {
                throw new RuntimeException("Has problem to parse process info: [$pidInfo][{$matches['pid']}][{$matches['lstart']}].", $exception->getCode(), $exception);
            }
        }

        return $pids;
    }

    /**
     * @param $pid
     * @return mixed|null
     */
    public function getPidStartTime($pid)
    {
        $pids = $this->ps();

        foreach ($pids as $pidInfo) {
            /** @noinspection TypeUnsafeComparisonInspection */
            if ($pid == $pidInfo['pid']) {
                return $pidInfo['lstart'];
            }
        }

        return null;
    }

    /**
     * Return info for pid
     *      [
     *          'id' => '1.123434123',
     *          'pid' => 1,
     *          'lstart' => 123434123,
     *      ]
     *
     * @param int $pid
     * @return array|null
     */
    public function pidInfo(int $pid): ?array
    {
        $pidInfo = array_filter($this->ps(), fn(array $pidInfo) => $pidInfo['pid'] === $pid);
        if (empty($pidInfo)) {
            return null;
        }
        return current($pidInfo);
    }

    /**
     * @param $pid
     * @param null $timestamp
     * @return string
     */
    public function createIdFromPidAndTimestamp($pid, $timestamp = null): string
    {
        $timestamp ??= time();

        return "{$pid}.{$timestamp}";
    }

}
