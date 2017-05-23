<?php

//$path = getcwd();
//if (!is_file($path . '/vendor/autoload.php')) {
//    $path = dirname(getcwd());
//}
//chdir($path);
chdir(__DIR__ . '/../../../rollun-callback/');

require './vendor/autoload.php';
require_once 'config/env_configurator.php';

use rollun\callback\Callback\CallbackException;
use rollun\dic\InsideConstruct;
use rollun\callback\Callback\Interruptor\Job;
use rollun\logger\Exception\LogExceptionLevel;

/** @var Zend\ServiceManager\ServiceManager $container */
$container = include './config/container.php';
InsideConstruct::setContainer($container);
$logger = new \rollun\logger\Logger();

$paramsString = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;

try {
    if (is_null($paramsString)) {
        throw new CallbackException('There is not params string', LogExceptionLevel::CRITICAL);
    }
    /* @var $job Job */
    $job = Job::unserializeBase64($paramsString);
    $callback = $job->getCallback();
    $value = $job->getValue();
    $logger->info("process with job [$paramsString] start.");
    call_user_func($callback, $value);
    $logger->info("process with job [$paramsString] finish.");
    exit(0);
} catch (\Exception $e) {
    $logger->error($e->getMessage());
    exit(1);
}
