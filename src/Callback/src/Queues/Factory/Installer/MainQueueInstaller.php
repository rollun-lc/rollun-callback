<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.04.17
 * Time: 16:15
 */

namespace rollun\callback\Queues\Factory\Installer;

use rollun\callback\Callback\Factory\CallbackAbstractFactoryAbstract;
use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Interrupter\Factory\InterruptAbstractFactoryAbstract;
use rollun\callback\Callback\Interrupter\Factory\ProcessAbstractFactory;
use rollun\callback\Callback\Interrupter\Process;
use rollun\callback\Callback\Multiplexer;
use rollun\callback\CronInstaller;
use rollun\callback\Queues\Factory\ExtractorAbstractFactory;
use rollun\callback\Queues\Factory\QueueAbstractFactory;
use rollun\installer\Install\InstallerAbstract;

class MainQueueInstaller extends InstallerAbstract
{
    const MAIN_SEC_QUEUE = 'mainSecQueue';
    const MAIN_SEC_QUEUE_EXTRACTOR  = 'mainSecQueueExtractor';
    const MAIN_SEC_QUEUE_EXTRACTOR_INTERRUPT  = 'mainSecQueueExtractorInterrupt';

    /**
     * install
     * @return array
     */
    public function install()
    {
        $config = [
            QueueAbstractFactory::KEY => [
                static::MAIN_SEC_QUEUE => []
            ],
            ExtractorAbstractFactory::KEY => [
                static::MAIN_SEC_QUEUE_EXTRACTOR => [
                    ExtractorAbstractFactory::KEY_QUEUE_SERVICE_NAME => static::MAIN_SEC_QUEUE
                ]
            ],
            InterruptAbstractFactoryAbstract::KEY => [
                static::MAIN_SEC_QUEUE_EXTRACTOR_INTERRUPT => [
                    ProcessAbstractFactory::KEY_CLASS => Process::class,
                    ProcessAbstractFactory::KEY_CALLBACK_SERVICE => static::MAIN_SEC_QUEUE_EXTRACTOR
                ],
            ],
            CallbackAbstractFactoryAbstract::KEY => [
                'sec_multiplexer' => [
                    MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
                    MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
                        static::MAIN_SEC_QUEUE_EXTRACTOR_INTERRUPT
                    ],
                ],
            ],
        ];

        return $config;
    }

    public function getName()
    {

        return static::class;
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        $result = (
            isset($config[QueueAbstractFactory::KEY][static::MAIN_SEC_QUEUE]) &&
            isset($config[ExtractorAbstractFactory::KEY][static::MAIN_SEC_QUEUE_EXTRACTOR]) &&
            isset($config[InterruptAbstractFactoryAbstract::KEY][static::MAIN_SEC_QUEUE_EXTRACTOR_INTERRUPT]) &&
            in_array(
                static::MAIN_SEC_QUEUE_EXTRACTOR_INTERRUPT,
                $config[CallbackAbstractFactoryAbstract::KEY]['sec_multiplexer']
                [MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES]
            )
        );
        return $result;
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
                $description = "Предоставляет возможность использовать предустаовленую главную очередь," .
                    "которая опрашиваеться на выполнение ежесекундно.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function isDefaultOn()
    {
        return false;
    }

    public function getDependencyInstallers()
    {
        return [
            QueueInstaller::class,
            CronInstaller::class,
            ExtractorInstaller::class
        ];
    }
}
