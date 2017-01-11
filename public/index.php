<?php
// try http://__zaboy-rest/api/rest/index_StoreMiddleware?fNumberOfHours=8&fWeekday=Monday
// Change to the project root, to simplify resolving paths
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
require_once 'config/env_configurator.php';

use rollun\callback\Callback\Pipe\Factory\CronReceiverFactory;
use rollun\callback\Callback\Pipe\Factory\HttpReceiverFactory;
use Zend\Diactoros\Server;
use rollun\datastore\Pipe\MiddlewarePipeOptions;

// Define application environment - 'dev' or 'prop'
if (getenv('APP_ENV') === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $env = 'develop';
}
$container = include 'config/container.php';

$HttpReceiverFactory = new HttpReceiverFactory();
$http = $HttpReceiverFactory($container, '');

$CronReceiverFactory = new CronReceiverFactory();
$cron = $CronReceiverFactory($container, '');

$app = new MiddlewarePipeOptions(['env' => isset($env) ? $env : null]); //['env' => 'develop']
$app->pipe('/api/http', $http);
$app->pipe('/api/cron', $cron);

$server = Server::createServer($app, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
$server->listen();
