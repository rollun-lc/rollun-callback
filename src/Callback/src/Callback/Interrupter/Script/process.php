<?php

$path = getcwd();
if (!is_file($path . '/vendor/autoload.php')) {
    $path = dirname(getcwd());
}
chdir($path);

require 'vendor/autoload.php';

use Jaeger\Tag\StringTag;
use Jaeger\Tracer\Tracer;
use Psr\Log\LoggerInterface;
use rollun\dic\InsideConstruct;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interrupter\Job;
use rollun\logger\LifeCycleToken;
use rollun\logger\Processor\ExceptionBacktrace;
use rollun\utils\FailedProcesses\Service\ProcessTracker;

$lifeCycleToken = LifeCycleToken::generateToken();

$paramsString = null;
$parentLifecycleToken = null;
$spanContext = null;

//Get argc
foreach ($argv as $i => $value) {
    if ($i === 1) {
        $paramsString = $value;
    } elseif (str_contains($value, 'lifecycleToken')) {
        [1 => $parentLifecycleToken] = explode(':', $value, 2);
    } elseif (str_contains($value, 'tracerContext')) {
        [1 => $tracerJsonContext] = explode(':', $value, 2);
        $spanContext = \rollun\utils\Json\Serializer::jsonUnserialize(base64_decode($tracerJsonContext));
    }
}

if ($parentLifecycleToken) {
    $lifeCycleToken->unserialize($parentLifecycleToken);
}

ProcessTracker::storeProcessData(
    $lifeCycleToken->toString(),
    $lifeCycleToken->hasParentToken() ? $lifeCycleToken->getParentToken()->toString() : null
);

/** @var Laminas\ServiceManager\ServiceManager $container */
$container = include 'config/container.php';
InsideConstruct::setContainer($container);

$container->setService(LifeCycleToken::class, $lifeCycleToken);

/** @var Tracer $tracer */
$tracer = $container->get(Tracer::class);

/** @var LoggerInterface $logger */
$logger = $container->get(LoggerInterface::class);

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($logger): void {
    if (! (error_reporting() & $errno)) {
        // error_reporting does not include this error
        return;
    }

    $exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);

    // Maybe in next releases we will throw this exceptions
    $logger->warning('When execute process, catch PHP error. But not throwing it.', [
        'exception' => $exception,
    ]);
});

try {
    $span = $tracer->start('process.php', [], $spanContext);
    if ($paramsString === null) {
        throw new CallbackException('There is not params string');
    }
    $logger->info("Interrupter 'Process' start.", [
        'memory' => memory_get_peak_usage(true),
    ]);
    /* @var $job Job */
    $job = Job::unserializeBase64($paramsString);
    $callback = $job->getCallback();
    $value = $job->getValue();
    //$logger->debug("Serialized job: $paramsString");
    call_user_func($callback, $value);
    $logger->info("Interrupter 'Process' finish.", [
        'memory' => memory_get_peak_usage(true),
    ]);
    $tracer->finish($span);
} catch (\Throwable $e) {
    $span->addTag(new StringTag('exception', json_encode((new ExceptionBacktrace())->getExceptionBacktrace($e))));
    $logger->error('When execute process, catch error', [
        'exception' => $e,
        'memory' => memory_get_peak_usage(true),
    ]);
} finally {
    $tracer->flush();
    ProcessTracker::clearProcessData();
}
