<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.03.17
 * Time: 11:20
 */

namespace rollun\callback\Middleware;

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadMiddlewareAbstractFactory;
use rollun\actionrender\Installers\ActionRenderInstaller;
use rollun\actionrender\Installers\BasicRenderInstaller;
use rollun\actionrender\MiddlewareDeterminator\Factory\AbstractMiddlewareDeterminatorAbstractFactory;
use rollun\actionrender\MiddlewareDeterminator\Factory\AttributeParamAbstractFactory;
use rollun\actionrender\MiddlewareDeterminator\Installers\AttributeParamInstaller;
use rollun\actionrender\MiddlewareDeterminator\Installers\HeaderSwitchInstaller;
use rollun\actionrender\Renderer\Json\JsonRenderer;
use rollun\callback\InterruptMiddlewareDeterminator;
use rollun\callback\Middleware\Factory\ImplicitInterruptorMiddlewareAbstractFactory;
use rollun\installer\Install\InstallerAbstract;

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
                    'httpCallback' => HttpInterruptorAction::class,
                ],
                "abstract_factories" => [
                    ImplicitInterruptorMiddlewareAbstractFactory::class,
                ]
            ],
            AbstractMiddlewareDeterminatorAbstractFactory::class => [
                InterruptMiddlewareDeterminator::class => [
                    AttributeParamAbstractFactory::KEY_NAME => "resourceName",
                    AttributeParamAbstractFactory::KEY_CLASS => InterruptMiddlewareDeterminator::class,
                ],
            ],
            LazyLoadMiddlewareAbstractFactory::KEY => [
                'webhookLLPipe' => [
                        LazyLoadMiddlewareAbstractFactory::KEY_MIDDLEWARE_DETERMINATOR => InterruptMiddlewareDeterminator::class
                    ],
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
            HeaderSwitchInstaller::class,
            AttributeParamInstaller::class,
        ];
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        return (
            isset($config['dependencies']['invokables']) &&
            isset($config[LazyLoadMiddlewareAbstractFactory::KEY]['webhookLLPipe']) &&
            isset($config[ActionRenderAbstractFactory::KEY]['webhookActionRender']) &&
            isset($config['dependencies']['invokables'][InterruptMiddlewareDeterminator::class]) &&
            $config['dependencies']['invokables'][InterruptMiddlewareDeterminator::class] ===
            InterruptMiddlewareDeterminator::class &&
            isset($config['dependencies']['invokables']['httpCallback'])
        );
    }


}
