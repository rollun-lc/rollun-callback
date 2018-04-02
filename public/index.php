<?php
// Delegate static file requests back to the PHP built-in webserver
use rollun\logger\LifeCycleToken;

if (php_sapi_name() === 'cli-server'
    && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))
) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';
require_once 'config/env_configurator.php';


/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */
call_user_func(function () {
    /** @var \Interop\Container\ContainerInterface $container */
    $container = require 'config/container.php';
    \rollun\dic\InsideConstruct::setContainer($container);
    //inject token to container
    $lifeCycleToke = LifeCycleToken::generateToken();
    if(apache_request_headers() && array_key_exists("LifeCycleToken", apache_request_headers())) {
        $lifeCycleToke->unserialize(apache_request_headers()["LifeCycleToken"]);
    }
    $container->setService(LifeCycleToken::class, $lifeCycleToke);

    /** @var \Zend\Expressive\Application $app */
    $app = $container->get(\Zend\Expressive\Application::class);

    // Import programmatic/declarative middleware pipeline and routing
    // configuration statements
    require 'config/pipeline.php';
    require 'config/routes.php';

    $app->run();
});
