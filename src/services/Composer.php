<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Composer\Config\JsonConfigSource;
use Composer\Factory;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class Composer service.
 *
 * An instance of the Composer service is globally accessible in Craft via [[Application::composer `Craft::$app->getComposer()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Composer extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Installs a given set of packages with Composer.
     *
     * @param array            $requirements Package name/version pairs
     * @param IOInterface|null $io           The IO object that Composer should be instantiated with
     *
     * @return void
     * @throws \Exception if something goes wrong
     */
    public function install(array $requirements, IOInterface $io = null)
    {
        if ($io === null) {
            $io = new NullIO();
        }

        // Get composer.json
        $jsonPath = $this->jsonPath();
        $backup = file_get_contents($jsonPath);

        // Set the working directory to the composer.json dir, in case there are any relative repo paths
        $wd = getcwd();
        chdir(dirname($jsonPath));

        try {
            // Update composer.json
            $sortPackages = Factory::create($io, $jsonPath)->getConfig()->get('sort-packages');
            $this->updateRequirements($jsonPath, $requirements, $sortPackages);

            // Run the installer
            $composer = Factory::create($io, $jsonPath);
            $installer = Installer::create($io, $composer)
                ->setPreferDist()
                ->setSkipSuggest()
                ->setUpdate()
                ->setUpdateWhitelist(array_keys($requirements));

            $status = $installer->run();
        } catch (\Exception $exception) {
            $status = 1;
        }

        // Change the working directory back
        chdir($wd);

        if ($status !== 0) {
            file_put_contents($jsonPath, $backup);
            throw $exception ?? new \Exception('An error occurred');
        }
    }

    /**
     * Uninstalls a given set of packages with Composer.
     *
     * @param string[]         $packages Package names
     * @param IOInterface|null $io       The IO object that Composer should be instantiated with
     *
     * @return void
     * @throws \Exception if something goes wrong
     */
    public function uninstall(array $packages, IOInterface $io = null)
    {
        $packages = array_map('strtolower', $packages);

        if ($io === null) {
            $io = new NullIO();
        }

        // Get composer.json
        $jsonPath = $this->jsonPath();
        $backup = file_get_contents($jsonPath);

        // Set the working directory to the composer.json dir, in case there are any relative repo paths
        $wd = getcwd();
        chdir(dirname($jsonPath));

        try {
            $jsonFile = new JsonFile($jsonPath);
            $jsonSource = new JsonConfigSource($jsonFile);
            $composerConfig = $jsonFile->read();

            // Make sure name checks are done case insensitively
            if (isset($composerConfig['require'])) {
                foreach ($composerConfig['require'] as $name => $version) {
                    $composerConfig['require'][strtolower($name)] = $name;
                }
            }

            // Remove the packages
            foreach ($packages as $package) {
                if (isset($composerConfig['require'][$package])) {
                    $jsonSource->removeLink('require', $composerConfig['require'][$package]);
                } else {
                    $io->writeError('<warning>'.$package.' is not required in your composer.json and has not been removed</warning>');
                }
            }

            $composer = Factory::create($io, $jsonPath);
            $composer->getDownloadManager()->setOutputProgress(false);

            // Run the installer
            $installer = Installer::create($io, $composer)
                ->setUpdate()
                ->setUpdateWhitelist($packages);

            $status = $installer->run();
        } catch (\Exception $exception) {
            $status = 1;
        }

        // Change the working directory back
        chdir($wd);

        if ($status !== 0) {
            file_put_contents($jsonPath, $backup);
            throw $exception ?? new \Exception('An error occurred');
        }
    }

    /**
     * Optimizes the Composer autoloader.
     *
     * @param IOInterface|null $io The IO object that Composer should be instantiated with
     *
     * @return void
     * @throws \Exception if something goes wrong
     */
    public function optimize(IOInterface $io = null)
    {
        if ($io === null) {
            $io = new NullIO();
        }

        $jsonPath = $this->jsonPath();

        // Set the working directory to the composer.json dir, in case there are any relative repo paths
        $wd = getcwd();
        chdir(dirname($jsonPath));

        try {
            $composer = Factory::create($io, $jsonPath);

            $installationManager = $composer->getInstallationManager();
            $localRepo = $composer->getRepositoryManager()->getLocalRepository();
            $package = $composer->getPackage();
            $config = $composer->getConfig();
            $authoritative = $config->get('classmap-authoritative');

            $generator = $composer->getAutoloadGenerator();
            $generator->setClassMapAuthoritative($authoritative);
            $generator->dump($config, $localRepo, $package, $installationManager, 'composer', true);
        } catch (\Exception $exception) {
        }

        // Change the working directory back
        chdir($wd);

        if (isset($exception)) {
            throw $exception;
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the path to composer.json.
     *
     * @return string
     * @throws Exception if composer.json can't be located
     */
    protected function jsonPath(): string
    {
        $jsonPath = defined('CRAFT_COMPOSER_PATH') ? CRAFT_COMPOSER_PATH : CRAFT_BASE_PATH.'/composer.json';
        if (!file_exists($jsonPath)) {
            throw new Exception('Could not locate your composer.json file.');
        }
        return $jsonPath;
    }

    /**
     * Updates the composer.json file with new requirements
     *
     * @param string $jsonPath
     * @param array  $requirements
     * @param bool   $sortPackages
     *
     * @return void
     */
    protected function updateRequirements(string $jsonPath, array $requirements, bool $sortPackages)
    {
        $requireKey = 'require';
        $removeKey = 'require-dev';

        // First try using JsonManipulator
        $success = true;
        $manipulator = new JsonManipulator(file_get_contents($jsonPath));

        foreach ($requirements as $package => $constraint) {
            if (
                !$manipulator->addLink($requireKey, $package, $constraint, $sortPackages) ||
                !$manipulator->removeSubNode($removeKey, $package)
            ) {
                $success = false;
                break;
            }
        }

        if ($success) {
            file_put_contents($jsonPath, $manipulator->getContents());
            return;
        }

        // There was a problem so do it manually instead
        $json = new JsonFile($jsonPath);
        $config = $json->read();

        foreach ($requirements as $package => $version) {
            $config[$requireKey][$package] = $version;
            unset($config[$removeKey][$package]);
        }

        $json->write($config);
    }
}
