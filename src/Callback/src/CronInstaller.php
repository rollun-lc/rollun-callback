<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15.03.17
 * Time: 13:40
 */

namespace rollun\callback;

use rollun\callback\Callback\Example\MinCallback;
use rollun\callback\Callback\Example\SecCallback;
use rollun\callback\Callback\Factory\CallbackAbstractFactoryAbstract;
use rollun\callback\Callback\Factory\Installer\MultiplexerInstaller;
use rollun\callback\Callback\Factory\Installer\TickerInstaller;
use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Factory\TickerAbstractFactory;
use rollun\callback\Callback\Interruptor\Factory\InterruptAbstractFactoryAbstract;
use rollun\callback\Callback\Interruptor\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interruptor\Process;
use rollun\callback\Callback\Interruptor\Script\ProcessInstaller;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\Callback\Ticker;
use rollun\callback\Middleware\MiddlewareInterruptorInstaller;
use rollun\installer\Install\InstallerAbstract;

class CronInstaller extends InstallerAbstract
{

    /**
     * install
     * @return array
     */
    public function install()
    {
        $config = [
            'dependencies' => [
                'abstract_factories' => [
                ],
                'invokables' => [
                    MinCallback::class => MinCallback::class,
                    SecCallback::class => SecCallback::class,
                ]
            ],
            CallbackAbstractFactoryAbstract::KEY => [
                'min_multiplexer' => [
                    MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
                    MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                        'interrupt_cron_sec_ticker'
                    ],
                ],
                'cron_sec_ticker' => [
                    TickerAbstractFactory::KEY_CLASS => Ticker::class,
                    TickerAbstractFactory::KEY_CALLBACK => 'interrupt_sec_multiplexer',
                ],
                'sec_multiplexer' => [
                    MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
                ],
            ],
            InterruptAbstractFactoryAbstract::KEY => [
                'cron' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'min_multiplexer'
                ],
                'interrupt_cron_sec_ticker' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'cron_sec_ticker'
                ],
                'interrupt_sec_multiplexer' => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'sec_multiplexer'
                ]
            ]
        ];

        if ($this->consoleIO->askConfirmation("Install cron multiplexer with Examples ? (Yes/No)")) {
            $config[CallbackAbstractFactoryAbstract::KEY]['min_multiplexer']
            [MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES] = [
                'interrupt_cron_sec_ticker',
                MinCallback::class,
                MinCallback::class,
                MinCallback::class,
                MinCallback::class,
            ];
            $config[CallbackAbstractFactoryAbstract::KEY]['sec_multiplexer']
            [MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES] = [
                SecCallback::class,
                SecCallback::class,
            ];
        }
        return $config;
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
            TickerInstaller::class,
            ProcessInstaller::class,
            MultiplexerInstaller::class,
        ];
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        return (
            isset($config['dependencies']['abstract_factories']) &&

            isset($config[CallbackAbstractFactoryAbstract::KEY]['sec_multiplexer']) &&
            isset($config[CallbackAbstractFactoryAbstract::KEY]['min_multiplexer']) &&
            isset($config[CallbackAbstractFactoryAbstract::KEY]['cron_sec_ticker']) &&

            isset($config[InterruptAbstractFactoryAbstract::KEY]['cron']) &&
            isset($config[InterruptAbstractFactoryAbstract::KEY]['interrupt_cron_sec_ticker']) &&
            isset($config[InterruptAbstractFactoryAbstract::KEY]['interrupt_sec_multiplexer']) &&

            isset($config['dependencies']['invokables'][MinCallback::class]) &&
            isset($config['dependencies']['invokables'][SecCallback::class])

        );
    }

}
