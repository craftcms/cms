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
use Composer\Util\Platform;
use Craft;
use craft\helpers\FileHelper;
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
     * Installs a given set of packages with Composer.
     *
     * @param array            $requirements Package name/version pairs
     * @param IOInterface|null $io           The IO object that Composer should be instantiated with
     *
     * @return void
     * @throws \Throwable if something goes wrong
     */
    public function install(array $requirements, IOInterface $io = null)
    {
        if ($io === null) {
            $io = new NullIO();
        }

        // Preload Composer classes in case Composer needs to self-update
        $this->preloadComposerClasses();

        // Get composer.json
        $jsonPath = $this->getJsonPath();
        $backup = file_get_contents($jsonPath);

        // Set the working directory to the composer.json dir, in case there are any relative repo paths
        $wd = getcwd();
        chdir(dirname($jsonPath));

        // Ensure there's a home var
        $this->_ensureHomeVar();

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
     * Uninstalls a given set of packages with Composer.
     *
     * @param string[]         $packages Package names
     * @param IOInterface|null $io       The IO object that Composer should be instantiated with
     *
     * @return void
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

            $composer = Factory::create($io, $jsonPath);
            $composer->getDownloadManager()->setOutputProgress(false);

            // Run the installer
            $installer = Installer::create($io, $composer)
                ->setUpdate()
                ->setUpdateWhitelist($packages);

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
     *
     * @return void
     * @throws \Throwable if something goes wrong
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
            $composer = Factory::create($io, $jsonPath);

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

    /**
     * Preloads Composer classes in case Composer needs to update itself
     */
    protected function preloadComposerClasses()
    {
        $classes = [
            \Composer\Factory::class,
            \Composer\Json\JsonFile::class,
            \JsonSchema\Validator::class,
            \JsonSchema\Constraints\BaseConstraint::class,
            \JsonSchema\Constraints\Factory::class,
            \JsonSchema\Constraints\Constraint::class,
            \JsonSchema\Constraints\ConstraintInterface::class,
            \JsonSchema\Uri\UriRetriever::class,
            \JsonSchema\UriRetrieverInterface::class,
            \JsonSchema\SchemaStorage::class,
            \JsonSchema\SchemaStorageInterface::class,
            \JsonSchema\Uri\UriResolver::class,
            \JsonSchema\UriResolverInterface::class,
            \JsonSchema\Iterator\ObjectIterator::class,
            \JsonSchema\Entity\JsonPointer::class,
            \JsonSchema\Constraints\SchemaConstraint::class,
            \JsonSchema\Constraints\UndefinedConstraint::class,
            \JsonSchema\Uri\Retrievers\FileGetContents::class,
            \JsonSchema\Uri\Retrievers\AbstractRetriever::class,
            \JsonSchema\Uri\Retrievers\UriRetrieverInterface::class,
            \JsonSchema\Constraints\TypeCheck\StrictTypeCheck::class,
            \JsonSchema\Constraints\TypeCheck\TypeCheckInterface::class,
            \JsonSchema\Constraints\TypeConstraint::class,
            \JsonSchema\Constraints\TypeCheck\LooseTypeCheck::class,
            \JsonSchema\Constraints\ObjectConstraint::class,
            \JsonSchema\Constraints\StringConstraint::class,
            \JsonSchema\Constraints\FormatConstraint::class,
            \JsonSchema\Constraints\EnumConstraint::class,
            \JsonSchema\Constraints\CollectionConstraint::class,
            \Seld\JsonLint\JsonParser::class,
            \Seld\JsonLint\Lexer::class,
            \Seld\JsonLint\Undefined::class,
            \Composer\Config::class,
            \Composer\Util\Platform::class,
            \Composer\Config\JsonConfigSource::class,
            \Composer\Config\ConfigSourceInterface::class,
            \Composer\Composer::class,
            \Composer\Util\ProcessExecutor::class,
            \Composer\Util\RemoteFilesystem::class,
            \Composer\CaBundle\CaBundle::class,
            \Psr\Log\LogLevel::class,
            \Composer\EventDispatcher\EventDispatcher::class,
            \Composer\Repository\RepositoryFactory::class,
            \Composer\Repository\RepositoryManager::class,
            \Composer\Repository\InstalledFilesystemRepository::class,
            \Composer\Repository\FilesystemRepository::class,
            \Composer\Repository\WritableArrayRepository::class,
            \Composer\Repository\ArrayRepository::class,
            \Composer\Repository\BaseRepository::class,
            \Composer\Repository\RepositoryInterface::class,
            \Composer\Repository\WritableRepositoryInterface::class,
            \Composer\Repository\InstalledRepositoryInterface::class,
            \Composer\Package\Version\VersionParser::class,
            \Composer\Semver\VersionParser::class,
            \Composer\Package\Version\VersionGuesser::class,
            \Composer\Package\Loader\RootPackageLoader::class,
            \Composer\Package\Loader\ArrayLoader::class,
            \Composer\Package\Loader\LoaderInterface::class,
            \Composer\Util\Git::class,
            \Symfony\Component\Process\Process::class,
            \Symfony\Component\Process\ProcessUtils::class,
            \Symfony\Component\Process\Pipes\UnixPipes::class,
            \Symfony\Component\Process\Pipes\AbstractPipes::class,
            \Symfony\Component\Process\Pipes\PipesInterface::class,
            \Composer\Util\Svn::class,
            \Composer\Package\RootPackage::class,
            \Composer\Package\CompletePackage::class,
            \Composer\Package\Package::class,
            \Composer\Package\BasePackage::class,
            \Composer\Package\PackageInterface::class,
            \Composer\Package\CompletePackageInterface::class,
            \Composer\Package\RootPackageInterface::class,
            \Composer\Semver\Constraint\Constraint::class,
            \Composer\Semver\Constraint\ConstraintInterface::class,
            \Composer\Package\Link::class,
            \Composer\Semver\Constraint\MultiConstraint::class,
            \Composer\Repository\ComposerRepository::class,
            \Composer\Repository\ConfigurableRepositoryInterface::class,
            \Composer\Cache::class,
            \Composer\Util\Filesystem::class,
            \Composer\Repository\PathRepository::class,
            \Composer\Installer\InstallationManager::class,
            \Composer\Downloader\DownloadManager::class,
            \Composer\Downloader\GitDownloader::class,
            \Composer\Downloader\VcsDownloader::class,
            \Composer\Downloader\DownloaderInterface::class,
            \Composer\Downloader\ChangeReportInterface::class,
            \Composer\Downloader\VcsCapableDownloaderInterface::class,
            \Composer\Downloader\DvcsDownloaderInterface::class,
            \Composer\Downloader\SvnDownloader::class,
            \Composer\Downloader\FossilDownloader::class,
            \Composer\Downloader\HgDownloader::class,
            \Composer\Downloader\PerforceDownloader::class,
            \Composer\Downloader\ZipDownloader::class,
            \Composer\Downloader\ArchiveDownloader::class,
            \Composer\Downloader\FileDownloader::class,
            \Composer\Downloader\RarDownloader::class,
            \Composer\Downloader\TarDownloader::class,
            \Composer\Downloader\GzipDownloader::class,
            \Composer\Downloader\XzDownloader::class,
            \Composer\Downloader\PharDownloader::class,
            \Composer\Downloader\PathDownloader::class,
            \Composer\Autoload\AutoloadGenerator::class,
            \Composer\Installer\LibraryInstaller::class,
            \Composer\Installer\InstallerInterface::class,
            \Composer\Installer\BinaryPresenceInterface::class,
            \Composer\Installer\BinaryInstaller::class,
            \Composer\Installer\PearInstaller::class,
            \Composer\Installer\PearBinaryInstaller::class,
            \Composer\Installer\PluginInstaller::class,
            \Composer\Installer\MetapackageInstaller::class,
            \Composer\Plugin\PluginManager::class,
            \Composer\Package\AliasPackage::class,
            \Composer\Semver\Constraint\EmptyConstraint::class,
            \Composer\Plugin\PluginInterface::class,
            \Composer\DependencyResolver\Pool::class,
            \yii\composer\Plugin::class,
            \Composer\EventDispatcher\EventSubscriberInterface::class,
            \yii\composer\Installer::class,
            \Composer\Installer\PackageEvents::class,
            \Composer\Script\ScriptEvents::class,
            \craft\composer\Plugin::class,
            \craft\composer\Installer::class,
            \Composer\Package\Locker::class,
            \Composer\Package\Dumper\ArrayDumper::class,
            \Composer\EventDispatcher\Event::class,
            \Composer\Plugin\PluginEvents::class,
            \Composer\Json\JsonManipulator::class,
            \Composer\Installer::class,
            \Composer\Script\Event::class,
            \Composer\Repository\PlatformRepository::class,
            \Composer\Repository\InstalledArrayRepository::class,
            \Composer\Repository\CompositeRepository::class,
            \Composer\Installer\SuggestedPackagesReporter::class,
            \Composer\DependencyResolver\DefaultPolicy::class,
            \Composer\DependencyResolver\PolicyInterface::class,
            \Composer\Plugin\PreFileDownloadEvent::class,
            \Composer\Util\StreamContextFactory::class,
            \Composer\DependencyResolver\Request::class,
            \Composer\Installer\InstallerEvents::class,
            \Composer\Installer\InstallerEvent::class,
            \Composer\DependencyResolver\Solver::class,
            \Composer\DependencyResolver\RuleSetGenerator::class,
            \Composer\DependencyResolver\RuleSet::class,
            \Composer\DependencyResolver\Rule::class,
            \Composer\DependencyResolver\GenericRule::class,
            \Composer\DependencyResolver\Rule2Literals::class,
            \Composer\DependencyResolver\Decisions::class,
            \Composer\DependencyResolver\RuleWatchGraph::class,
            \Composer\DependencyResolver\RuleSetIterator::class,
            \Composer\DependencyResolver\RuleWatchNode::class,
            \Composer\DependencyResolver\RuleWatchChain::class,
            \Composer\DependencyResolver\Transaction::class,
            \Composer\DependencyResolver\Operation\UpdateOperation::class,
            \Composer\DependencyResolver\Operation\SolverOperation::class,
            \Composer\DependencyResolver\Operation\OperationInterface::class,
            \Composer\Installer\PackageEvent::class,
            \Symfony\Component\Finder\Finder::class,
            \Symfony\Component\Finder\Comparator\NumberComparator::class,
            \Symfony\Component\Finder\Comparator\Comparator::class,
            \Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator::class,
            \Symfony\Component\Finder\Iterator\DepthRangeFilterIterator::class,
            \Symfony\Component\Finder\Iterator\FilterIterator::class,
            \Symfony\Component\Finder\SplFileInfo::class,
            \Composer\Util\Silencer::class,
            \Composer\Autoload\ClassMapGenerator::class,
            \Symfony\Component\Finder\Iterator\FileTypeFilterIterator::class,
            \Symfony\Component\Finder\Iterator\ExcludeDirectoryFilterIterator::class,
            \Symfony\Component\Finder\Iterator\FilenameFilterIterator::class,
            \Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator::class,
            \Symfony\Component\Finder\Iterator\PathFilterIterator::class,
        ];

        foreach ($classes as $class) {
            class_exists($class, true);
        }
    }
}
