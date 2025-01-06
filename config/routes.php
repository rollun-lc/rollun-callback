<?php
/**
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Action\HomePageAction::class, 'home');
 * $app->post('/album', App\Action\AlbumCreateAction::class, 'album.create');
 * $app->put('/album/:id', App\Action\AlbumUpdateAction::class, 'album.put');
 * $app->patch('/album/:id', App\Action\AlbumUpdateAction::class, 'album.patch');
 * $app->delete('/album/:id', App\Action\AlbumDeleteAction::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Action\ContactAction::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Action\ContactAction::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Action\ContactAction::class,
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */

use Psr\Container\ContainerInterface;
use rollun\callback\Middleware\WebhookMiddleware;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Route;

/**
 * Setup middleware pipeline:
 * @param Application $app
 * @param MiddlewareFactory $factory
 * @param ContainerInterface $container
 * @return void
 */
return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->route(
        '/api/webhook/{resourceName}',
        WebhookMiddleware::class,
        Route::HTTP_METHOD_ANY,
        'webhook'
    );
};
