<?php
require 'vendor/autoload.php';
require_once 'config/env_configurator.php';
$container = require 'config/container.php';
\rollun\dic\InsideConstruct::setContainer($container);

$httpInterrupt = new \rollun\callback\Callback\Interruptor\Http(function($value) {
    $file = fopen(\rollun\installer\Command::getDataDir() . $value, "w+");
    fwrite($file, "$value");
}, "http://localhost:8080/webhook/httpCallback");
$httpInterrupt("first");
//$httpInterrupt("second");