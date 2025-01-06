<?php

use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;

error_reporting(E_ALL ^ E_USER_DEPRECATED ^ E_DEPRECATED);

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/** @var Laminas\ServiceManager\ServiceManager $container */
$container = require 'config/container.php';
InsideConstruct::setContainer($container);

$lifeCycleToken = LifeCycleToken::generateToken();
$container->setService(LifeCycleToken::class, $lifeCycleToken);

//$callback = $container->get('testHealthChecker');
//$callback = unserialize(serialize($callback));
//$callback();

echo 'Done !';
die();
