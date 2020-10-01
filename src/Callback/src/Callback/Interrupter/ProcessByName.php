<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\callback\Callback\Interrupter;

use Jaeger\Tracer\Tracer;
use Psr\Log\LoggerInterface;
use ReflectionException;
use rollun\callback\Callback\CallbackException;
use rollun\callback\PidKiller\PidKillerInterface;
use rollun\callback\Promise\Interfaces\PayloadInterface;
use rollun\callback\Promise\SimplePayload;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;
use rollun\utils\Json\Serializer;

/**
 * Class Process
 * @package rollun\callback\Callback\Interrupter
 */
class ProcessByName implements InterrupterInterface
{
    const STDOUT_KEY = 'stdout';
    const STDERR_KEY = 'stderr';
    const PID_KEY = 'pid';

    const SCRIPT_PATH = '/Script/service.php';

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
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var string
     */
    private $callableServiceName;

    /**
     * Process constructor.
     * @param string $callableServiceName
     * @param PidKillerInterface|null $pidKiller
     * @param int|null $maxExecuteTime
     * @param LoggerInterface|null $logger
     * @param LifeCycleToken|null $lifecycleToken
     * @param Tracer|null $tracer
     * @throws ReflectionException
     */
    public function __construct(
        string $callableServiceName,
        $pidKiller = null,
        int $maxExecuteTime = null,
        LoggerInterface $logger = null,
        LifeCycleToken $lifecycleToken = null,
        Tracer $tracer = null
    ) {
        InsideConstruct::setConstructParams([
            "lifecycleToken" => LifeCycleToken::class,
            'tracer' => Tracer::class,
            'logger' => LoggerInterface::class
        ]);
        $this->pidKiller = $pidKiller;
        $this->maxExecuteTime = $maxExecuteTime;
        $this->callableServiceName = $callableServiceName;
    }

    public function __sleep()
    {
        return [
            'callback',
            'pidKiller',
            'maxExecuteTime',
            'callableServiceName',
        ];
    }


    public function __wakeup()
    {
        InsideConstruct::initWakeup([
            "lifecycleToken" => LifeCycleToken::class,
            'tracer' => Tracer::class,
            'logger' => LoggerInterface::class
        ]);
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
        $traserContext = base64_encode(Serializer::jsonSerialize($span->getContext()));


        $cmd = "php {$this->getScriptName()} {$this->callableServiceName}"
            . " lifecycleToken:{$this->lifecycleToken->serialize()}"
            . " tracerContext:$traserContext"
            . ' APP_ENV=' . getenv('APP_ENV');

        $outStream = getenv('OUTPUT_STREAM');
        if ($outStream) {
            $payload[self::STDOUT_KEY] = $outStream;
            $payload[self::STDERR_KEY] = $outStream;
        } else {
            $payload[self::STDOUT_KEY] = '/dev/null';
            $payload[self::STDERR_KEY] = '/dev/null';

        }
        $payload['interrupter_type'] = self::class;

        $cmd .= "  1>{$payload[self::STDOUT_KEY]} 2>{$payload[self::STDERR_KEY]}";

        if (substr(php_uname(), 0, 7) !== "Windows") {
            $cmd .= " & echo $!";
        }

        //fix not found context problem
        $this->tracer->flush();
        $pid = trim(shell_exec($cmd));

        if ($this->maxExecuteTime && $this->pidKiller) {
            $record = [
                'name' => $this->callableServiceName,
                'delaySeconds' => $this->maxExecuteTime,
                'pid' => $pid,
            ];
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
