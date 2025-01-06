<?php

declare(strict_types=1);

use rollun\dic\InsideConstruct;
use rollun\logger\LifeCycleToken;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Laminas\ServiceManager\ServiceManager;

error_reporting(E_ALL ^ E_USER_DEPRECATED);

// Delegate static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server'
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */
call_user_func(function () {
    /** @var ServiceManager $container */
    $container = require 'config/container.php';
    InsideConstruct::setContainer($container);

    /** @var Application $app */
    $app = $container->get(Application::class);
    $factory = $container->get(MiddlewareFactory::class);

    // Import programmatic/declarative middleware pipeline and routing
    // configuration statements
    (require 'config/pipeline.php')($app, $factory, $container);
    (require 'config/routes.php')($app, $factory, $container);

    // Init lifecycle token
    $lifeCycleToken = LifeCycleToken::generateToken();

    if (LifeCycleToken::getAllHeaders() && array_key_exists("LifeCycleToken", LifeCycleToken::getAllHeaders())) {
        $lifeCycleToken->unserialize(LifeCycleToken::getAllHeaders()["LifeCycleToken"]);
    }

    $container->setService(LifeCycleToken::class, $lifeCycleToken);

    $app->run();
});
