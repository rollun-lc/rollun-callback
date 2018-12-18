<?php
global $argv;

use PHPUnit\Framework\Error\Deprecated;
use rollun\logger\LifeCycleToken;

error_reporting(E_ALL);
Deprecated::$enabled = false;

// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);

if (getenv("APP_ENV") != 'dev') {
    echo "You cannot start test if environment var APP_ENV not set in dev!";
    exit(1);
}

// Init lifecycle token
$lifeCycleToken = LifeCycleToken::generateToken();

if (LifeCycleToken::getAllHeaders() && array_key_exists("LifeCycleToken", LifeCycleToken::getAllHeaders())) {
    $lifeCycleToken->unserialize(LifeCycleToken::getAllHeaders()["LifeCycleToken"]);
}

$container->setService(LifeCycleToken::class, $lifeCycleToken);
