<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 06.04.17
 * Time: 16:15
 */

namespace rollun\callback\Callback\Factory\Installer;

use rollun\callback\Callback\Factory\MultiplexerAbstractFactory;
use rollun\callback\Callback\Factory\ServiceCallbackAbstractFactory;
use rollun\callback\Callback\Factory\TickerAbstractFactory;
use rollun\callback\Callback\Multiplexer;
use rollun\installer\Install\InstallerAbstract;

class ServiceCallbackInstaller extends InstallerAbstract
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
                    ServiceCallbackAbstractFactory::class,
                ],
            ],
        ];

        //TODO: add generator for abstract instance
        /*if ($this->consoleIO->askConfirmation("Use the default Multiplexer for the configuration?")) {
            $clientName = Multiplexer::class;
        } else {
            $clientName = $this->askParams("Enter the name for the Multiplexer configuration:");
        }*/
        return $config;
    }

    public function getName() {
        /*if($this->consoleIO->askConfirmation("Do you want to check for a non-default client ?")) {
            $clientName = $this->askParams("Enter the name for the Multiplexer configuration:");
        } else {
            $clientName = Multiplexer::class;
        }*/

        return static::class;
    }

    public function isInstall()
    {
        $config = $this->container->get('config');
        $result = (
            isset($config['dependencies']['abstract_factories']) &&
            in_array(ServiceCallbackAbstractFactory::class, $config['dependencies']['abstract_factories'])
        );
        /*if($result) {
            $clientName = $this->getName();
            $result = in_array($clientName, $config[MultiplexerAbstractFactory::KEY]);
        }*/
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
                $description = "Предоставляет возможность использовать заданый метод заданного сервиса в качестве вызываемой функции.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function isDefaultOn()
    {
        return true;
    }

    public function getDependencyInstallers()
    {
        return [

        ];
    }
}
