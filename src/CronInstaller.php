<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.03.17
 * Time: 13:40
 */

namespace rollun\callback;

use rollun\actionrender\Factory\ActionRenderAbstractFactory;
use rollun\actionrender\Factory\LazyLoadPipeAbstractFactory;
use rollun\actionrender\Installers\ActionRenderInstaller;
use rollun\actionrender\Installers\BasicRenderInstaller;
use rollun\actionrender\Installers\MiddlewarePipeInstaller;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AbstractLazyLoadMiddlewareGetterAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\AttributeAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\Factory\ResponseRendererAbstractFactory;
use rollun\actionrender\LazyLoadMiddlewareGetter\ResponseRenderer;
use rollun\actionrender\Renderer\Json\JsonRendererAction;
use rollun\callback\Callback\Interruptor\Factory\InterruptorAbstractFactoryAbstract;
use rollun\callback\Callback\Interruptor\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Interruptor\Factory\TickerAbstractFactory;
use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Callback\Interruptor\Ticker;
use rollun\callback\Example\CronMinMultiplexer;
use rollun\callback\Example\CronSecMultiplexer;
use rollun\callback\LazyLoadInterruptMiddlewareGetter;
use rollun\callback\Middleware\HttpInterruptorAction;
use rollun\callback\Middleware\MiddlewareInterruptorInstaller;
use rollun\installer\Install\InstallerAbstract;
use rollun\promise\Entity\EntityInstaller;
use rollun\promise\Promise\PromiseInstaller;

class CronInstaller extends InstallerAbstract
{

    /**
     * install
     * @return array
     */
    public function install()
    {
        return [
            'dependencies' => [
                'abstract_factories' => [
                    AttributeAbstractFactory::class,
                    MultiplexerAbstractFactory::class,
                    TickerAbstractFactory::class,
                ]
            ],

            InterruptorAbstractFactoryAbstract::KEY => [
                'cron' => [
                    MultiplexerAbstractFactory::KEY_CLASS => CronMinMultiplexer::class,
                    MultiplexerAbstractFactory::KEY_INTERRUPTERS_SERVICE => [
                        'cron_sec_ticker'
                    ]
                ],
                'cron_sec_ticker' => [
                    TickerAbstractFactory::KEY_CLASS => Ticker::class,
                    TickerAbstractFactory::KEY_CALLBACK => 'sec_multiplexer',
                ],
                'sec_multiplexer' => [
                    MultiplexerAbstractFactory::KEY_CLASS => CronSecMultiplexer::class,
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
                $description = "Дает возможность использовать фабрику мультиплекторов и интерапторов. 
                Создает базовую реализаю обработчика крон.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function getDependencyInstallers()
    {
        return [
            MiddlewareInterruptorInstaller::class,
        ];
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        return (
            isset($config['dependencies']['abstract_factories']) &&
            isset($config[InterruptorAbstractFactoryAbstract::KEY]['sec_multiplexer']) &&
            isset($config[InterruptorAbstractFactoryAbstract::KEY]['min_multiplexer']) &&
            isset($config[InterruptorAbstractFactoryAbstract::KEY]['cron_sec_ticker']) &&
            isset($config[InterruptorAbstractFactoryAbstract::KEY]['cron']) &&
            in_array(AttributeAbstractFactory::class, $config['dependencies']['abstract_factories']) &&
            in_array(MultiplexerAbstractFactory::class, $config['dependencies']['abstract_factories']) &&
            in_array(TickerAbstractFactory::class, $config['dependencies']['abstract_factories'])
        );
    }

}
