<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Support;

/**
 * The callback behind the `cronCallback` test service.
 *
 * It is a named invokable rather than a closure on purpose. The cron webhook hands the whole
 * multiplexer to Interrupter\Process, which serializes it; a closure would be routed through
 * opis/closure and, on a PHP hit by GH-8995, would fail to unpack in the child process. The
 * webhook would still answer with a PID, so the cron test would see a success and no work done.
 */
class CronCallback
{
    public const OUTPUT_FILE = 'data' . DIRECTORY_SEPARATOR . 'interrupt_min';

    public function __invoke($value = null): array
    {
        $time = microtime(true);

        file_put_contents(
            self::OUTPUT_FILE,
            'MIN_FILE_NAME' . ": {$value} [" . microtime(true) . "]\n",
            FILE_APPEND
        );

        return [$time];
    }
}
