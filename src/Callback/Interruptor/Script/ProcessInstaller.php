<?php

/**
 * Zaboy lib (http://zaboy.org/lib/)
 *
 * @copyright  Zaboychenko Andrey
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

namespace rollun\callback\Callback\Interruptor\Script;

use Interop\Container\ContainerInterface;
use rollun\callback\Callback\Interruptor\Process;
use rollun\installer\Install\InstallerAbstract;
use rollun\promise\Entity\EntityInstaller;
use rollun\promise\Promise\PromiseInstaller;

/**
 * Installer class
 *
 * @category   Zaboy
 * @package    zaboy
 */
class ProcessInstaller extends InstallerAbstract
{
    public function install()
    {
        $reflection = new \ReflectionClass(self::class);
        $scriptFile = realpath(dirname($reflection->getFileName()) . DIRECTORY_SEPARATOR . Process::FILE_NAME);
        if (file_exists($scriptFile)) {
            @mkdir(Process::PATH_SCRIPT_DATA, 0777, true);
            copy(
                $scriptFile,
                getcwd() . DIRECTORY_SEPARATOR . Process::PATH_SCRIPT_DATA . Process::FILE_NAME
            );
        }
        if (!file_exists(getcwd() . DIRECTORY_SEPARATOR . Process::PATH_SCRIPT_DATA . Process::FILE_NAME)) {
            throw new \RuntimeException(
                'Can not create file: '
                . getcwd() . DIRECTORY_SEPARATOR . Process::PATH_SCRIPT_DATA . Process::FILE_NAME
            );
        }
    }

    /**
     * Clean all installation
     * @return void
     */
    public function uninstall()
    {
        if (file_exists(getcwd() . DIRECTORY_SEPARATOR . Process::PATH_SCRIPT_DATA . Process::FILE_NAME)) {
            unlink(getcwd() . DIRECTORY_SEPARATOR . Process::PATH_SCRIPT_DATA . Process::FILE_NAME);
        }
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
                $description = "Скрипт для запуска callback в другом процессе.";
                break;
            default:
                $description = "Does not exist.";
        }
        return $description;
    }

    public function isInstall()
    {
        return (file_exists(getcwd() . DIRECTORY_SEPARATOR . Process::PATH_SCRIPT_DATA . Process::FILE_NAME));
    }

    public function getDependencyInstallers()
    {
        return [
            PromiseInstaller::class,
            EntityInstaller::class,
        ];
    }


}
