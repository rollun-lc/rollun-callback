<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter;

use Jaeger\Tracer\Tracer;
use ReflectionException;
use rollun\callback\Callback\CallbackException;
use rollun\callback\PidKiller\InfoProviderInterface;
use rollun\callback\PidKiller\PidKillerInterface;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
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

    /** @var integer */
    protected $maxExecuteTime;

    /** @var PidKillerInterface */
    protected $pidKiller;

    /** @var Tracer */
    protected $tracer;

    /**
     * Process constructor.
     * @param callable $callback
     * @param PidKillerInterface|null $pidKiller
     * @param int|null $maxExecuteTime
     * @param LifeCycleToken|null $lifecycleToken
     * @throws ReflectionException
     */
    public function __construct(
        callable $callback,
        $pidKiller = null,
        int $maxExecuteTime = null,
        LifeCycleToken $lifecycleToken = null,
        Tracer $tracer = null
    ) {
        InsideConstruct::setConstructParams(["lifecycleToken" => LifeCycleToken::class, 'tracer' => Tracer::class]);
        parent::__construct($callback);

        $this->pidKiller = $pidKiller;
        $this->maxExecuteTime = $maxExecuteTime;
    }

    /**
     * @param $value
     * @return PayloadInterface
     * @throws ReflectionException
     */
    public function __invoke($value = null): PayloadInterface
    {
        $span = $this->tracer->start('Process::__invoke');

        $context = $span->getContext();
        $traserContext = base64_encode(\rollun\utils\Json\Serializer::jsonSerialize($span->getContext()));


        $cmd = 'php ' . $this->getScriptName();

        $job = new Job($this->callback, $value);

        $serializedJob = $job->serializeBase64();
        $cmd .= ' ' . $serializedJob;
        $cmd .= " lifecycleToken:{$this->lifecycleToken->serialize()}";
        $cmd .= " tracerContext:$traserContext";
        $cmd .= ' APP_ENV=' . getenv('APP_ENV');

        $outStream = getenv('OUTPUT_STREAM');
        if ($outStream) {
            $payload[self::STDOUT_KEY] = $outStream;
            $payload[self::STDERR_KEY] = $outStream;
        } else {
            $payload[self::STDOUT_KEY] = '/dev/null';
            $payload[self::STDERR_KEY] = '/dev/null';

        }
        $payload[static::INTERRUPTER_TYPE_KEY] = $this->getInterrupterType();

        $cmd .= "  1>{$payload[self::STDOUT_KEY]} 2>{$payload[self::STDERR_KEY]}";

        if (substr(php_uname(), 0, 7) !== "Windows") {
            $cmd .= " & echo $!";
        }

        $pid = trim(shell_exec($cmd));

        if ($this->maxExecuteTime && $this->pidKiller) {
            $record = [
                'delaySeconds' => $this->maxExecuteTime,
                'pid' => $pid,
            ];
            if ($this->callback instanceof InfoProviderInterface) {
                $record['info'] = $this->callback->getInfo();
            }
            $this->pidKiller->create($record);
        }

        $payload = new SimplePayload($pid, $payload);
        $this->tracer->finish($span);
        return $payload;
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
