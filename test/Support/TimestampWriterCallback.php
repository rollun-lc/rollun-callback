<?php

/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace Rollun\Test\Support;

/**
 * Appends the current timestamp to a file, as a named invokable object.
 *
 * Callbacks that cross an Interrupter\Process boundary get serialized, and the callback
 * kind decides how: a closure is routed through opis/closure, an anonymous class cannot
 * be serialized at all, and only a named class the composer autoloader can resolve is
 * reconstructible in the child process.
 */
class TimestampWriterCallback
{
    public function __construct(private int $sleepSeconds = 0) {}

    public function __invoke($file = null): void
    {
        if ($this->sleepSeconds > 0) {
            sleep($this->sleepSeconds);
        }

        file_put_contents($file, microtime(true) . "\n", FILE_APPEND);
    }
}
