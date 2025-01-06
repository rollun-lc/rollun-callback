<?php
global $argv;

use PHPUnit\Framework\Error\Deprecated;
use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;
use Symfony\Component\Dotenv\Dotenv;

error_reporting(E_ALL);
//Deprecated::$enabled = false;

// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));

$container = require 'config/container.php';
InsideConstruct::setContainer($container);

// Make environment variables stored in .env accessible via getenv(), $_ENV or $_SERVER.
if(file_exists('.env')) {
    (new Dotenv())->usePutenv(true)->load('.env');
}

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
