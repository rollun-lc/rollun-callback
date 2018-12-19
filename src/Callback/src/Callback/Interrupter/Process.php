<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter;

use ReflectionException;
use rollun\callback\Callback\CallbackException;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;

/**
 * Class Process
 * @package rollun\callback\Callback\Interrupter
 */
class Process extends InterrupterAbstract
{
    const CALLBACK_KEY = 'callback';
    const VALUE_KEY = 'value';

    const STDOUT_KEY = 'stdout';
    const STDERR_KEY = 'stderr';
    const PID_KEY = 'pid';

    const SCRIPT_PATH = '/Script/process.php';

    /**
     * @var LifecycleToken
     */
    protected $lifecycleToken;

    /**
     * Process constructor.
     * @param callable $callback
     * @param LifeCycleToken|null $lifecycleToken
     * @throws ReflectionException
     */
    public function __construct(callable $callback, LifeCycleToken $lifecycleToken = null)
    {
        InsideConstruct::setConstructParams(["lifecycleToken" => LifeCycleToken::class]);
        parent::__construct($callback);
    }

    /**
     * @param $value
     * @return PromiseInterface
     * @throws ReflectionException
     */
    public function __invoke($value): PromiseInterface
    {
        $cmd = 'php ' . $this->getScriptName();

        $job = new Job($this->callback, $value);

        $serializedJob = $job->serializeBase64();
        $cmd .= ' ' . $serializedJob;
        $cmd .= " {$this->lifecycleToken->serialize()}";
        $cmd .= ' APP_ENV=' . getenv('APP_ENV');

        $result[self::STDOUT_KEY] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('stdout_', 1);
        $result[self::STDERR_KEY] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('stderr_', 1);
        $result[static::INTERRUPTER_TYPE_KEY] = $this->getInterrupterType();

        $cmd .= "  1>{$result[self::STDOUT_KEY]} 2>{$result[self::STDERR_KEY]}";

        if (substr(php_uname(), 0, 7) !== "Windows") {
            $cmd .= " & echo $!";
        }

        $result[self::PID_KEY] = trim(shell_exec($cmd));

        // TODO: must return implementation of PromiseInterface
        return $result;
    }

    /**
     * @return string
     */
    protected function getScriptName(): string
    {
        $scriptPath = __DIR__ . self::SCRIPT_PATH;

        if (!is_file($scriptPath)) {
            throw new CallbackException(sprintf("File '%s' not found", realpath($scriptPath)));
        }

        return $scriptPath;
    }
}