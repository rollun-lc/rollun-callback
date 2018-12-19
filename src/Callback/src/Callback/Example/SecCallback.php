<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Example;

use rollun\callback\Callback\Interrupter\InterrupterInterface;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;

class SecCallback implements InterrupterInterface
{
    const FILE = 'interrupt_sec';

    const PREFIX_TEXT = 'SEC_FILE_NAME';

    /**
     * @param $value
     * @return PayloadInterface
     * array contains field
     */
    public function __invoke($value): PayloadInterface
    {
        $time = microtime(true);
        $dataDir = realpath('./') . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

        file_put_contents(
            $dataDir . DIRECTORY_SEPARATOR . static::FILE,
            static::PREFIX_TEXT . ": {$value} [" . microtime(true) . "]\n",
            FILE_APPEND
        );

        return new SimplePayload(null, ['time' => $time]);
    }
}
