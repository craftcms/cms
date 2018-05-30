<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Composer\Config\JsonConfigSource;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Package\Locker;
use Composer\Util\Platform;
use Craft;
use craft\composer\Factory;
use craft\helpers\FileHelper;
use Seld\JsonLint\DuplicateKeyException;
use Seld\JsonLint\JsonParser;
use yii\base\Component;
use yii\base\Exception;

/**
 * Composer service.
 * An instance of the Composer service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getComposer()|`Craft::$app->composer`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Composer extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $composerRepoUrl = 'https://composer.craftcms.com/';

    /**
     * @var bool
     */
    public $disablePackagist = true;

    /**
     * @var bool Whether to generate a new Composer class map, rather than preloading all of the classes in the current class map
     */
    public $updateComposerClassMap = false;

    /**
     * @var string[]|null
     */
    private $_composerClasses;

    // Public Methods
    // =========================================================================

    /**
     * Returns the path to composer.json.
     *
     * @return string
     * @throws Exception if composer.json can't be located
     */
    public function getJsonPath(): string
    {
        $jsonPath = defined('CRAFT_COMPOSER_PATH') ? CRAFT_COMPOSER_PATH : Craft::getAlias('@root/composer.json');
        if (!file_exists($jsonPath)) {
            throw new Exception('Could not locate your composer.json file.');
        }
        return $jsonPath;
    }

    /**
     * Returns the path to composer.lock, if it exists.
     *
     * @return string|null
     * @throws Exception if composer.json can't be located
     */
    public function getLockPath()
    {
        $jsonPath = $this->getJsonPath();
        // Logic based on \Composer\Factory::createComposer()
        $lockPath = pathinfo($jsonPath, PATHINFO_EXTENSION) === 'json'
            ? substr($jsonPath, 0, -4).'lock'
            : $jsonPath.'.lock';
        return file_exists($lockPath) ? $lockPath : null;
    }

    /**
     * Installs a given set of packages with Composer.
     *
     * @param array $requirements Package name/version pairs
     * @param IOInterface|null $io The IO object that Composer should be instantiated with
     * @throws \Throwable if something goes wrong
     */
    public function install(array $requirements, IOInterface $io = null)
    {
        if ($io === null) {
            $io = new NullIO();
        }

        // Get composer.json
        $jsonPath = $this->getJsonPath();
        $backup = file_get_contents($jsonPath);

        // Set the working directory to the composer.json dir, in case there are any relative repo paths
        $wd = getcwd();
        chdir(dirname($jsonPath));

        // Ensure there's a home var
        $this->_ensureHomeVar();

        // Update composer.json with the new (optimized) requirements
        $optimized = Craft::$app->getApi()->getOptimizedComposerRequirements($requirements, []);
        $this->updateRequirements($jsonPath, $optimized, false);

        if ($this->updateComposerClassMap) {
            // Start logging newly-autoloaded classes
            $this->_composerClasses = [];
            spl_autoload_register([$this, 'logComposerClass'], true, true);
        } else {
            // Preload Composer classes in case Composer needs to self-update
            $this->preloadComposerClasses();
        }

        // Run the installer
        $composer = $this->createComposer($io, $jsonPath);
        $installer = Installer::create($io, $composer)
            ->setPreferDist()
            ->setSkipSuggest()
            ->setUpdate()
            ->setDumpAutoloader()
            ->setOptimizeAutoloader(true);

        try {
            $status = $installer->run();
        } catch (\Throwable $exception) {
            $status = 1;
        }

        // Change the working directory back
        chdir($wd);

        if ($this->updateComposerClassMap) {
            // Generate a new composer-classes.php
            spl_autoload_unregister([$this, 'logComposerClass']);
            $contents = "<?php\n\nreturn [\n";
            sort($this->_composerClasses);
            foreach ($this->_composerClasses as $class) {
                $contents .= "    '{$class}',\n";
            }
            $contents .= "];\n";
            FileHelper::writeToFile(dirname(__DIR__).'/config/composer-classes.php', $contents);
        }

        // Return composer.json to normal
        file_put_contents($jsonPath, $backup);

        if ($status !== 0) {
            throw $exception ?? new \Exception('An error occurred');
        }

        // Update composer.json with the new (non-optimized) requirements
        $sortPackages = $this->createComposer($io, $jsonPath, false)->getConfig()->get('sort-packages');
        $this->updateRequirements($jsonPath, $requirements, $sortPackages);
    }

    /**
     * Uninstalls a given set of packages with Composer.
     *
     * @param string[] $packages Package names
     * @param IOInterface|null $io The IO object that Composer should be instantiated with
     * @throws \Throwable if something goes wrong
     */
    public function uninstall(array $packages, IOInterface $io = null)
    {
        $packages = array_map('strtolower', $packages);

        if ($io === null) {
            $io = new NullIO();
        }

        // Get composer.json
        $jsonPath = $this->getJsonPath();
        $backup = file_get_contents($jsonPath);

        // Set the working directory to the composer.json dir, in case there are any relative repo paths
        $wd = getcwd();
        chdir(dirname($jsonPath));

        // Ensure there's a home var
        $this->_ensureHomeVar();

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

            $composer = $this->createComposer($io, $jsonPath);
            $composer->getDownloadManager()->setOutputProgress(false);

            // Run the installer
            $installer = Installer::create($io, $composer)
                ->setUpdate()
                ->setUpdateWhitelist($packages)
                ->setDumpAutoloader()
                ->setOptimizeAutoloader(true);

            $status = $installer->run();
        } catch (\Throwable $exception) {
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
     * @throws \Throwable if something goes wrong
     * @deprecated
     */
    public function optimize(IOInterface $io = null)
    {
        if ($io === null) {
            $io = new NullIO();
        }

        $jsonPath = $this->getJsonPath();

        // Set the working directory to the composer.json dir, in case there are any relative repo paths
        $wd = getcwd();
        chdir(dirname($jsonPath));

        // Ensure there's a home var
        $this->_ensureHomeVar();

        try {
            $composer = $this->createComposer($io, $jsonPath);

            $installationManager = $composer->getInstallationManager();
            $localRepo = $composer->getRepositoryManager()->getLocalRepository();
            $package = $composer->getPackage();
            $config = $composer->getConfig();
            $authoritative = $config->get('classmap-authoritative');

            $generator = $composer->getAutoloadGenerator();
            $generator->setClassMapAuthoritative($authoritative);
            $generator->dump($config, $localRepo, $package, $installationManager, 'composer', true);
        } catch (\Throwable $exception) {
            // Swallow exception.
        }

        // Change the working directory back
        chdir($wd);

        if (isset($exception)) {
            throw $exception;
        }
    }

    /**
     * Adds an autoloading class to the Composer class map
     *
     * @param string $className
     */
    public function logComposerClass(string $className)
    {
        $this->_composerClasses[] = $className;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Ensures that HOME/APPDATA or COMPOSER_HOME env vars have been set.
     */
    protected function _ensureHomeVar()
    {
        if (getenv('COMPOSER_HOME') !== false) {
            return;
        }

        $alt = Platform::isWindows() ? 'APPDATA' : 'HOME';
        if (getenv($alt) !== false) {
            return;
        }

        // Just define one ourselves
        $path = Craft::$app->getPath()->getRuntimePath().DIRECTORY_SEPARATOR.'composer';
        FileHelper::createDirectory($path);
        putenv("COMPOSER_HOME={$path}");
    }

    /**
     * Updates the composer.json file with new requirements
     *
     * @param string $jsonPath
     * @param array $requirements
     * @param bool $sortPackages
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

    /**
     * Returns the decoded Composer config, modified to use
     * composer.craftcms.com instead of packagist.org.
     *
     * @param IOInterface $io
     * @param string $jsonPath
     * @param bool $swapPackagist
     * @return array
     */
    protected function composerConfig(IOInterface $io, string $jsonPath, bool $swapPackagist = true): array
    {
        // Copied from \Composer\Factory::createComposer()
        $file = new JsonFile($jsonPath, null, $io);
        $file->validateSchema(JsonFile::LAX_SCHEMA);
        $jsonParser = new JsonParser;
        try {
            $jsonParser->parse(file_get_contents($jsonPath), JsonParser::DETECT_KEY_CONFLICTS);
        } catch (DuplicateKeyException $e) {
            $details = $e->getDetails();
            $io->writeError('<warning>Key '.$details['key'].' is a duplicate in '.$jsonPath.' at line '.$details['line'].'</warning>');
        }
        $config = $file->read();

        if ($swapPackagist) {
            // Add composer.craftcms.com if it's not already in there
            if (!$this->findCraftRepo($config)) {
                $config['repositories'][] = ['type' => 'composer', 'url' => $this->composerRepoUrl];
            }

            // Disable Packagist if it's not already disabled
            if ($this->disablePackagist && !$this->findDisablePackagist($config)) {
                $config['repositories'][] = ['packagist.org' => false];
            }
        }

        return $config;
    }

    protected function findCraftRepo(array $config): bool
    {
        if (!isset($config['repositories'])) {
            return false;
        }

        foreach ($config['repositories'] as $repository) {
            if (isset($repository['url']) && $repository['url'] === $this->composerRepoUrl) {
                return true;
            }
        }

        return false;
    }

    protected function findDisablePackagist(array $config): bool
    {
        if (!isset($config['repositories'])) {
            return false;
        }

        foreach ($config['repositories'] as $repository) {
            if ($repository === ['packagist.org' => false]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Creates a new Composer instance.
     *
     * @param IOInterface $io
     * @param string $jsonPath
     * @param bool $swapPackagist
     * @return \Composer\Composer
     */
    protected function createComposer(IOInterface $io, string $jsonPath, bool $swapPackagist = true): \Composer\Composer
    {
        $config = $this->composerConfig($io, $jsonPath, $swapPackagist);
        $composer = Factory::create($io, $config);
        $lockFile = pathinfo($jsonPath, PATHINFO_EXTENSION) === 'json'
            ? substr($jsonPath, 0, -4).'lock'
            : $jsonPath.'.lock';
        $rm = $composer->getRepositoryManager();
        $im = $composer->getInstallationManager();
        $locker = new Locker($io, new JsonFile($lockFile, null, $io), $rm, $im, file_get_contents($jsonPath));
        $composer->setLocker($locker);
        return $composer;
    }

    /**
     * Preloads Composer classes in case Composer needs to update itself
     */
    protected function preloadComposerClasses()
    {
        $classes = require dirname(__DIR__).'/config/composer-classes.php';

        foreach ($classes as $class) {
            class_exists($class, true);
        }
    }
}
