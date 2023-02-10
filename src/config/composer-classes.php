<?php

return [
    Composer\Autoload\AutoloadGenerator::class,
    Composer\Autoload\ClassLoader::class,
    Composer\Autoload\ClassMapGenerator::class,
    Composer\Autoload\PhpFileCleaner::class,
    Composer\CaBundle\CaBundle::class,
    Composer\Cache::class,
    Composer\Compiler::class,
    Composer\Composer::class,
    Composer\Config::class,
    Composer\Config\ConfigSourceInterface::class,
    Composer\Config\JsonConfigSource::class,
    Composer\DependencyResolver\Decisions::class,
    Composer\DependencyResolver\DefaultPolicy::class,
    Composer\DependencyResolver\GenericRule::class,
    Composer\DependencyResolver\LocalRepoTransaction::class,
    Composer\DependencyResolver\LockTransaction::class,
    Composer\DependencyResolver\MultiConflictRule::class,
    Composer\DependencyResolver\Operation\InstallOperation::class,
    Composer\DependencyResolver\Operation\MarkAliasInstalledOperation::class,
    Composer\DependencyResolver\Operation\MarkAliasUninstalledOperation::class,
    Composer\DependencyResolver\Operation\OperationInterface::class,
    Composer\DependencyResolver\Operation\SolverOperation::class,
    Composer\DependencyResolver\Operation\UninstallOperation::class,
    Composer\DependencyResolver\Operation\UpdateOperation::class,
    Composer\DependencyResolver\PolicyInterface::class,
    Composer\DependencyResolver\Pool::class,
    Composer\DependencyResolver\PoolBuilder::class,
    Composer\DependencyResolver\PoolOptimizer::class,
    Composer\DependencyResolver\Problem::class,
    Composer\DependencyResolver\Request::class,
    Composer\DependencyResolver\Rule2Literals::class,
    Composer\DependencyResolver\Rule::class,
    Composer\DependencyResolver\RuleSet::class,
    Composer\DependencyResolver\RuleSetGenerator::class,
    Composer\DependencyResolver\RuleSetIterator::class,
    Composer\DependencyResolver\RuleWatchChain::class,
    Composer\DependencyResolver\RuleWatchGraph::class,
    Composer\DependencyResolver\RuleWatchNode::class,
    Composer\DependencyResolver\Solver::class,
    Composer\DependencyResolver\SolverBugException::class,
    Composer\DependencyResolver\SolverProblemsException::class,
    Composer\DependencyResolver\Transaction::class,
    Composer\Downloader\ArchiveDownloader::class,
    Composer\Downloader\ChangeReportInterface::class,
    Composer\Downloader\DownloadManager::class,
    Composer\Downloader\DownloaderInterface::class,
    Composer\Downloader\DvcsDownloaderInterface::class,
    Composer\Downloader\FileDownloader::class,
    Composer\Downloader\FilesystemException::class,
    Composer\Downloader\FossilDownloader::class,
    Composer\Downloader\GitDownloader::class,
    Composer\Downloader\GzipDownloader::class,
    Composer\Downloader\HgDownloader::class,
    Composer\Downloader\MaxFileSizeExceededException::class,
    Composer\Downloader\PathDownloader::class,
    Composer\Downloader\PerforceDownloader::class,
    Composer\Downloader\PharDownloader::class,
    Composer\Downloader\RarDownloader::class,
    Composer\Downloader\SvnDownloader::class,
    Composer\Downloader\TarDownloader::class,
    Composer\Downloader\TransportException::class,
    Composer\Downloader\VcsCapableDownloaderInterface::class,
    Composer\Downloader\VcsDownloader::class,
    Composer\Downloader\XzDownloader::class,
    Composer\Downloader\ZipDownloader::class,
    Composer\EventDispatcher\Event::class,
    Composer\EventDispatcher\EventDispatcher::class,
    Composer\EventDispatcher\EventSubscriberInterface::class,
    Composer\EventDispatcher\ScriptExecutionException::class,
    Composer\Exception\IrrecoverableDownloadException::class,
    Composer\Exception\NoSslException::class,
    Composer\Factory::class,
    Composer\Filter\PlatformRequirementFilter\IgnoreAllPlatformRequirementFilter::class,
    Composer\Filter\PlatformRequirementFilter\IgnoreListPlatformRequirementFilter::class,
    Composer\Filter\PlatformRequirementFilter\IgnoreNothingPlatformRequirementFilter::class,
    Composer\Filter\PlatformRequirementFilter\PlatformRequirementFilterFactory::class,
    Composer\Filter\PlatformRequirementFilter\PlatformRequirementFilterInterface::class,
    Composer\IO\BaseIO::class,
    Composer\IO\BufferIO::class,
    Composer\IO\ConsoleIO::class,
    Composer\IO\IOInterface::class,
    Composer\IO\NullIO::class,
    Composer\InstalledVersions::class,
    Composer\Installer::class,
    Composer\Installer\BinaryInstaller::class,
    Composer\Installer\BinaryPresenceInterface::class,
    Composer\Installer\InstallationManager::class,
    Composer\Installer\InstallerEvent::class,
    Composer\Installer\InstallerEvents::class,
    Composer\Installer\InstallerInterface::class,
    Composer\Installer\LibraryInstaller::class,
    Composer\Installer\MetapackageInstaller::class,
    Composer\Installer\NoopInstaller::class,
    Composer\Installer\PackageEvent::class,
    Composer\Installer\PackageEvents::class,
    Composer\Installer\PluginInstaller::class,
    Composer\Installer\ProjectInstaller::class,
    Composer\Installer\SuggestedPackagesReporter::class,
    Composer\Json\JsonFile::class,
    Composer\Json\JsonFormatter::class,
    Composer\Json\JsonManipulator::class,
    Composer\Json\JsonValidationException::class,
    Composer\MetadataMinifier\MetadataMinifier::class,
    Composer\Package\AliasPackage::class,
    Composer\Package\Archiver\ArchivableFilesFilter::class,
    Composer\Package\Archiver\ArchivableFilesFinder::class,
    Composer\Package\Archiver\ArchiveManager::class,
    Composer\Package\Archiver\ArchiverInterface::class,
    Composer\Package\Archiver\BaseExcludeFilter::class,
    Composer\Package\Archiver\ComposerExcludeFilter::class,
    Composer\Package\Archiver\GitExcludeFilter::class,
    Composer\Package\Archiver\PharArchiver::class,
    Composer\Package\Archiver\ZipArchiver::class,
    Composer\Package\BasePackage::class,
    Composer\Package\Comparer\Comparer::class,
    Composer\Package\CompleteAliasPackage::class,
    Composer\Package\CompletePackage::class,
    Composer\Package\CompletePackageInterface::class,
    Composer\Package\Dumper\ArrayDumper::class,
    Composer\Package\Link::class,
    Composer\Package\Loader\ArrayLoader::class,
    Composer\Package\Loader\InvalidPackageException::class,
    Composer\Package\Loader\JsonLoader::class,
    Composer\Package\Loader\LoaderInterface::class,
    Composer\Package\Loader\RootPackageLoader::class,
    Composer\Package\Loader\ValidatingArrayLoader::class,
    Composer\Package\Locker::class,
    Composer\Package\Package::class,
    Composer\Package\PackageInterface::class,
    Composer\Package\RootAliasPackage::class,
    Composer\Package\RootPackage::class,
    Composer\Package\RootPackageInterface::class,
    Composer\Package\Version\StabilityFilter::class,
    Composer\Package\Version\VersionGuesser::class,
    Composer\Package\Version\VersionParser::class,
    Composer\Package\Version\VersionSelector::class,
    Composer\Platform\HhvmDetector::class,
    Composer\Platform\Runtime::class,
    Composer\Platform\Version::class,
    Composer\Plugin\Capability\Capability::class,
    Composer\Plugin\Capability\CommandProvider::class,
    Composer\Plugin\Capable::class,
    Composer\Plugin\CommandEvent::class,
    Composer\Plugin\PluginBlockedException::class,
    Composer\Plugin\PluginEvents::class,
    Composer\Plugin\PluginInterface::class,
    Composer\Plugin\PluginManager::class,
    Composer\Plugin\PostFileDownloadEvent::class,
    Composer\Plugin\PreCommandRunEvent::class,
    Composer\Plugin\PreFileDownloadEvent::class,
    Composer\Plugin\PrePoolCreateEvent::class,
    Composer\Question\StrictConfirmationQuestion::class,
    Composer\Repository\ArrayRepository::class,
    Composer\Repository\ArtifactRepository::class,
    Composer\Repository\ComposerRepository::class,
    Composer\Repository\CompositeRepository::class,
    Composer\Repository\ConfigurableRepositoryInterface::class,
    Composer\Repository\FilesystemRepository::class,
    Composer\Repository\FilterRepository::class,
    Composer\Repository\InstalledArrayRepository::class,
    Composer\Repository\InstalledFilesystemRepository::class,
    Composer\Repository\InstalledRepository::class,
    Composer\Repository\InstalledRepositoryInterface::class,
    Composer\Repository\InvalidRepositoryException::class,
    Composer\Repository\LockArrayRepository::class,
    Composer\Repository\PackageRepository::class,
    Composer\Repository\PathRepository::class,
    Composer\Repository\PearRepository::class,
    Composer\Repository\PlatformRepository::class,
    Composer\Repository\RepositoryFactory::class,
    Composer\Repository\RepositoryInterface::class,
    Composer\Repository\RepositoryManager::class,
    Composer\Repository\RepositorySecurityException::class,
    Composer\Repository\RepositorySet::class,
    Composer\Repository\RootPackageRepository::class,
    Composer\Repository\VcsRepository::class,
    Composer\Repository\Vcs\FossilDriver::class,
    Composer\Repository\Vcs\GitBitbucketDriver::class,
    Composer\Repository\Vcs\GitDriver::class,
    Composer\Repository\Vcs\GitHubDriver::class,
    Composer\Repository\Vcs\GitLabDriver::class,
    Composer\Repository\Vcs\HgDriver::class,
    Composer\Repository\Vcs\PerforceDriver::class,
    Composer\Repository\Vcs\SvnDriver::class,
    Composer\Repository\Vcs\VcsDriver::class,
    Composer\Repository\Vcs\VcsDriverInterface::class,
    Composer\Repository\VersionCacheInterface::class,
    Composer\Repository\WritableArrayRepository::class,
    Composer\Repository\WritableRepositoryInterface::class,
    Composer\Script\Event::class,
    Composer\Script\ScriptEvents::class,
    Composer\SelfUpdate\Keys::class,
    Composer\SelfUpdate\Versions::class,
    Composer\Semver\Comparator::class,
    Composer\Semver\CompilingMatcher::class,
    Composer\Semver\Constraint\Bound::class,
    Composer\Semver\Constraint\Constraint::class,
    Composer\Semver\Constraint\ConstraintInterface::class,
    Composer\Semver\Constraint\MatchAllConstraint::class,
    Composer\Semver\Constraint\MatchNoneConstraint::class,
    Composer\Semver\Constraint\MultiConstraint::class,
    Composer\Semver\Interval::class,
    Composer\Semver\Intervals::class,
    Composer\Semver\Semver::class,
    Composer\Semver\VersionParser::class,
    Composer\Spdx\SpdxLicenses::class,
    Composer\Util\AuthHelper::class,
    Composer\Util\Bitbucket::class,
    Composer\Util\ComposerMirror::class,
    Composer\Util\ConfigValidator::class,
    Composer\Util\ErrorHandler::class,
    Composer\Util\Filesystem::class,
    Composer\Util\Git::class,
    Composer\Util\GitHub::class,
    Composer\Util\GitLab::class,
    Composer\Util\Hg::class,
    Composer\Util\HttpDownloader::class,
    Composer\Util\Http\CurlDownloader::class,
    Composer\Util\Http\CurlResponse::class,
    Composer\Util\Http\ProxyHelper::class,
    Composer\Util\Http\ProxyManager::class,
    Composer\Util\Http\RequestProxy::class,
    Composer\Util\Http\Response::class,
    Composer\Util\IniHelper::class,
    Composer\Util\Loop::class,
    Composer\Util\MetadataMinifier::class,
    Composer\Util\NoProxyPattern::class,
    Composer\Util\PackageSorter::class,
    Composer\Util\Perforce::class,
    Composer\Util\Platform::class,
    Composer\Util\ProcessExecutor::class,
    Composer\Util\RemoteFilesystem::class,
    Composer\Util\Silencer::class,
    Composer\Util\StreamContextFactory::class,
    Composer\Util\Svn::class,
    Composer\Util\SyncHelper::class,
    Composer\Util\Tar::class,
    Composer\Util\TlsHelper::class,
    Composer\Util\Url::class,
    Composer\Util\Zip::class,
    GuzzleHttp\Client::class,
    GuzzleHttp\ClientInterface::class,
    GuzzleHttp\HandlerStack::class,
    GuzzleHttp\Handler\CurlFactory::class,
    GuzzleHttp\Handler\CurlFactoryInterface::class,
    GuzzleHttp\Handler\CurlHandler::class,
    GuzzleHttp\Handler\CurlMultiHandler::class,
    GuzzleHttp\Handler\EasyHandle::class,
    GuzzleHttp\Handler\Proxy::class,
    GuzzleHttp\Handler\StreamHandler::class,
    GuzzleHttp\Middleware::class,
    GuzzleHttp\PrepareBodyMiddleware::class,
    GuzzleHttp\Promise\FulfilledPromise::class,
    GuzzleHttp\Promise\Promise::class,
    GuzzleHttp\Promise\PromiseInterface::class,
    GuzzleHttp\Promise\TaskQueue::class,
    GuzzleHttp\Promise\TaskQueueInterface::class,
    GuzzleHttp\Psr7\MessageTrait::class,
    GuzzleHttp\Psr7\Request::class,
    GuzzleHttp\Psr7\Response::class,
    GuzzleHttp\Psr7\Stream::class,
    GuzzleHttp\Psr7\Uri::class,
    GuzzleHttp\Psr7\UriResolver::class,
    GuzzleHttp\RedirectMiddleware::class,
    GuzzleHttp\RequestOptions::class,
    GuzzleHttp\Utils::class,
    JsonSchema\Constraints\BaseConstraint::class,
    JsonSchema\Constraints\CollectionConstraint::class,
    JsonSchema\Constraints\Constraint::class,
    JsonSchema\Constraints\ConstraintInterface::class,
    JsonSchema\Constraints\EnumConstraint::class,
    JsonSchema\Constraints\Factory::class,
    JsonSchema\Constraints\FormatConstraint::class,
    JsonSchema\Constraints\ObjectConstraint::class,
    JsonSchema\Constraints\SchemaConstraint::class,
    JsonSchema\Constraints\StringConstraint::class,
    JsonSchema\Constraints\TypeCheck\LooseTypeCheck::class,
    JsonSchema\Constraints\TypeCheck\StrictTypeCheck::class,
    JsonSchema\Constraints\TypeCheck\TypeCheckInterface::class,
    JsonSchema\Constraints\TypeConstraint::class,
    JsonSchema\Constraints\UndefinedConstraint::class,
    JsonSchema\Entity\JsonPointer::class,
    JsonSchema\SchemaStorage::class,
    JsonSchema\SchemaStorageInterface::class,
    JsonSchema\UriResolverInterface::class,
    JsonSchema\UriRetrieverInterface::class,
    JsonSchema\Uri\Retrievers\AbstractRetriever::class,
    JsonSchema\Uri\Retrievers\FileGetContents::class,
    JsonSchema\Uri\Retrievers\UriRetrieverInterface::class,
    JsonSchema\Uri\UriResolver::class,
    JsonSchema\Uri\UriRetriever::class,
    JsonSchema\Validator::class,
    Psr\Http\Message\MessageInterface::class,
    Psr\Http\Message\RequestInterface::class,
    Psr\Http\Message\ResponseInterface::class,
    Psr\Http\Message\StreamInterface::class,
    Psr\Http\Message\UriInterface::class,
    Psr\Log\LogLevel::class,
    React\Promise\CancellablePromiseInterface::class,
    React\Promise\CancellationQueue::class,
    React\Promise\ExtendedPromiseInterface::class,
    React\Promise\FulfilledPromise::class,
    React\Promise\Promise::class,
    React\Promise\PromiseInterface::class,
    React\Promise\RejectedPromise::class,
    Seld\JsonLint\JsonParser::class,
    Seld\JsonLint\Lexer::class,
    Seld\JsonLint\Undefined::class,
    Symfony\Component\Filesystem\Exception\ExceptionInterface::class,
    Symfony\Component\Filesystem\Exception\FileNotFoundException::class,
    Symfony\Component\Filesystem\Exception\IOException::class,
    Symfony\Component\Filesystem\Exception\IOExceptionInterface::class,
    Symfony\Component\Filesystem\Exception\InvalidArgumentException::class,
    Symfony\Component\Filesystem\Filesystem::class,
    Symfony\Component\Finder\Comparator\Comparator::class,
    Symfony\Component\Finder\Comparator\DateComparator::class,
    Symfony\Component\Finder\Comparator\NumberComparator::class,
    Symfony\Component\Finder\Finder::class,
    Symfony\Component\Finder\Glob::class,
    Symfony\Component\Finder\Iterator\DateRangeFilterIterator::class,
    Symfony\Component\Finder\Iterator\DepthRangeFilterIterator::class,
    Symfony\Component\Finder\Iterator\ExcludeDirectoryFilterIterator::class,
    Symfony\Component\Finder\Iterator\FileTypeFilterIterator::class,
    Symfony\Component\Finder\Iterator\FilenameFilterIterator::class,
    Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator::class,
    Symfony\Component\Finder\Iterator\PathFilterIterator::class,
    Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator::class,
    Symfony\Component\Finder\SplFileInfo::class,
    Symfony\Component\Process\ExecutableFinder::class,
    Symfony\Component\Process\Pipes\AbstractPipes::class,
    Symfony\Component\Process\Pipes\PipesInterface::class,
    Symfony\Component\Process\Pipes\UnixPipes::class,
    Symfony\Component\Process\Process::class,
    Symfony\Component\Process\ProcessUtils::class,
    craft\composer\Installer::class,
    craft\composer\Plugin::class,
    yii\composer\Installer::class,
    yii\composer\Plugin::class,
];
