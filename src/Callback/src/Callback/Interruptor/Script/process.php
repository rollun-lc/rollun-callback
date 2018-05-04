<?php

$path = getcwd();
if (!is_file($path . '/vendor/autoload.php')) {
    $path = dirname(getcwd());
}
chdir($path);

require 'vendor/autoload.php';
require_once 'config/env_configurator.php';

use Psr\Log\LoggerInterface;
use rollun\dic\InsideConstruct;
use rollun\callback\Callback\CallbackException;
use rollun\callback\Callback\Interruptor\Job;
use rollun\logger\LifeCycleToken;

/** @var Zend\ServiceManager\ServiceManager $container */
$container = include 'config/container.php';
InsideConstruct::setContainer($container);
$lifeCycleToke = LifeCycleToken::generateToken();
if(isset($_SERVER['argv'][2])) {
    $lifeCycleToke->unserialize($_SERVER['argv'][2]);
}
$container->setService(LifeCycleToken::class, $lifeCycleToke);
$logger = $container->get(LoggerInterface::class);

try {
    $paramsString = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;
    if (is_null($paramsString)) {
        throw new CallbackException('There is not params string');
    }
    /* @var $job Job */
    $job = Job::unserializeBase64($paramsString);
    $callback = $job->getCallback();
    $value = $job->getValue();
    $logger->info("process with job [$paramsString] start.");
    call_user_func($callback, $value);
    $logger->info("process with job [$paramsString] finish.");
    exit(0);
} catch (\Throwable $e) {
    $logger->error($e->getMessage());
    exit(1);
}
