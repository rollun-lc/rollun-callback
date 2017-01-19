<?php

//$path = getcwd();
//if (!is_file($path . '/vendor/autoload.php')) {
//    $path = dirname(getcwd());
//}
//chdir($path);
chdir(__DIR__ . '/../../../../');

require './vendor/autoload.php';
require_once 'config/env_configurator.php';

use rollun\callback\Callback\CallbackException;
use rollun\dic\InsideConstruct;
use rollun\callback\Callback\Interruptor\Job;

/** @var Zend\ServiceManager\ServiceManager $container */
$container = include './config/container.php';
InsideConstruct::setContainer($container);

$paramsString = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : null;

try {
    if (is_null($paramsString)) {
        throw new CallbackException('There is not params string');
    }
    /* @var $job Job */
    $job = Job::unserializeBase64($paramsString);
    $callback = $job->getCallback();
    $value = $job->getValue();
    call_user_func($callback, $value);
    exit(0);
} catch (\Exception $e) {
    exit(1);
}

