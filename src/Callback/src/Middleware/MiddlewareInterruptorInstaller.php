<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.03.17
 * Time: 11:20
 */

namespace rollun\callback\Middleware;

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadPipeAbstractFactory;
use rollun\actionrender\Installers\ActionRenderInstaller;
use rollun\actionrender\Installers\BasicRenderInstaller;
use rollun\actionrender\Installers\MiddlewarePipeInstaller;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AbstractLazyLoadMiddlewareGetterAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AttributeAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\ResponseRendererAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\ResponseRenderer;
use rollun\actionrender\Renderer\Json\JsonRenderer;
use rollun\actionrender\Renderer\Json\JsonRendererAction;
use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Example\CronMinMultiplexer;
use rollun\callback\Example\CronSecMultiplexer;
use rollun\callback\InterruptMiddlewareDeterminator;
use rollun\installer\Install\InstallerAbstract;
use rollun\promise\Entity\EntityInstaller;
use rollun\promise\Promise\PromiseInstaller;

class MiddlewareInterruptorInstaller extends InstallerAbstract
{

    /**
     * install
     * @return array
     */
    public function install()
    {
        return [
            'dependencies' => [
                'invokables' => [
                    InterruptMiddlewareDeterminator::class => InterruptMiddlewareDeterminator::class,
                    'httpCallback' => HttpInterruptorAction::class,
                ],

            ],
            LazyLoadPipeAbstractFactory::KEY => [
                'webhookLLPipe' => InterruptMiddlewareDeterminator::class,
            ],
            ActionRenderAbstractFactory::KEY => [
                'webhookActionRender' => [
                    ActionRenderAbstractFactory::KEY_ACTION_MIDDLEWARE_SERVICE => 'webhookLLPipe',
                    ActionRenderAbstractFactory::KEY_RENDER_MIDDLEWARE_SERVICE => JsonRenderer::class
                ],
            ],
        ];
    }

    /**
     * Clean all installation
     * @return void
     */
    public function uninstall()
    {
        // TODO: Implement uninstall() method.
    }

    /**
     * Return string with description of installable functional.
     * @param string $lang ; set select language for description getted.
     * @return string
     */
    public function getDescription($lang = "en")
    {
        switch ($lang) {
            case "ru":
                $description = "Базовая настройка для досутпа к итерапторам по http.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function getDependencyInstallers()
    {
        return [
            ActionRenderInstaller::class,
            BasicRenderInstaller::class,
        ];
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        return (
            isset($config['dependencies']['invokables']) &&
            isset($config[LazyLoadPipeAbstractFactory::KEY]['webhookLLPipe']) &&
            isset($config[ActionRenderAbstractFactory::KEY]['webhookActionRender']) &&
            isset($config['dependencies']['invokables'][InterruptMiddlewareDeterminator::class]) &&
            $config['dependencies']['invokables'][InterruptMiddlewareDeterminator::class] ===
            InterruptMiddlewareDeterminator::class &&
            isset($config['dependencies']['invokables']['httpCallback'])
        );
    }


}
