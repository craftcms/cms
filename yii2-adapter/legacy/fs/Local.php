<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

use Craft;
use craft\base\Fs;
use craft\base\LocalFsInterface;
use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use craft\helpers\FileHelper;
use craft\helpers\Path;
use craft\models\FsListing;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Security;
use Generator;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;
use Throwable;
use yii\validators\InlineValidator;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Local represents a local filesystem.
 *
 * @property-read mixed $settingsHtml
 * @property-read string $rootPath
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Local extends Fs implements LocalFsInterface
{
    public const VISIBILITY_FILE = 'file';
    public const VISIBILITY_DIR = 'dir';

    /**
     * @var int[][] Visibility map
     */
    protected array $visibilityMap = [
        self::VISIBILITY_FILE => [
            self::VISIBILITY_DEFAULT => 0644,
            self::VISIBILITY_PUBLIC => 0644,
            self::VISIBILITY_HIDDEN => 0600,
        ],
        self::VISIBILITY_DIR => [
            self::VISIBILITY_DEFAULT => 0775,
            self::VISIBILITY_PUBLIC => 0775,
            self::VISIBILITY_HIDDEN => 0700,
        ],
    ];

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return t('Local Folder');
    }

    /**
     * @var string|null Path to the root of this sources local folder.
     */
    public ?string $path = null;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (isset($config['path'])) {
            $config['path'] = rtrim(str_replace('\\', '/', $config['path']), '/');
            if ($config['path'] === '') {
                unset($config['path']);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $generalConfig = Cms::config();

        if ($generalConfig->defaultFileMode) {
            $this->visibilityMap[self::VISIBILITY_FILE][self::VISIBILITY_DEFAULT] = $generalConfig->defaultFileMode;
        }

        if ($generalConfig->defaultFileMode) {
            $this->visibilityMap[self::VISIBILITY_DIR][self::VISIBILITY_DEFAULT] = $generalConfig->defaultDirMode;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'path' => t('Base Path'),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['path'], 'required'];
        $rules[] = [['path'], 'validatePath'];
        return $rules;
    }

    /**
     * @param string $attribute
     * @param array|null $params
     * @param InlineValidator $validator
     * @return void
     * @since 4.4.6
     */
    public function validatePath(string $attribute, ?array $params, InlineValidator $validator): void
    {
        if (Security::isSystemDir($this->getRootPath())) {
            $validator->addError($this, $attribute, t('Local filesystems cannot be located within or above system directories.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return $this->settingsHtml(false);
    }

    /**
     * @inheritdoc
     */
    public function getReadOnlySettingsHtml(): ?string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        return template('_components/fs/Local/settings', [
            'volume' => $this,
            'readOnly' => $readOnly,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        // If the folder doesn't exist yet, create it with a .gitignore file
        $path = $this->getRootPath();

        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
            FileHelper::writeGitignoreFile($path);
        }

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function getRootPath(): string
    {
        $path = FileHelper::normalizePath(Env::parse($this->path) ?? '');
        // Pass it through realpath() in case the path is symlinked
        return realpath($path) ?: $path;
    }

    public function getDiskConfig(): array
    {
        $config = [
            'driver' => 'local',
            'root' => $this->getRootPath(),
            'permissions' => [
                'file' => [
                    'public' => $this->visibilityMap[self::VISIBILITY_FILE][self::VISIBILITY_PUBLIC],
                    'private' => $this->visibilityMap[self::VISIBILITY_FILE][self::VISIBILITY_HIDDEN],
                ],
                'dir' => [
                    'public' => $this->visibilityMap[self::VISIBILITY_DIR][self::VISIBILITY_PUBLIC],
                    'private' => $this->visibilityMap[self::VISIBILITY_DIR][self::VISIBILITY_HIDDEN],
                ],
            ],
            'visibility' => $this->defaultDiskVisibility(self::VISIBILITY_FILE),
            'directory_visibility' => $this->defaultDiskVisibility(self::VISIBILITY_DIR),
        ];

        $rootUrl = $this->getRootUrl();
        if ($rootUrl !== null) {
            $config['url'] = rtrim($rootUrl, '/');
        }

        return $config;
    }

    /**
     * @inheritdoc
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $directory = $this->normalizeDiskPath($directory);

        try {
            $fileList = $this->storageDisk()->listContents($directory, $recursive);
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            return;
        }

        foreach ($fileList as $listing) {
            if (!$listing instanceof StorageAttributes) {
                continue;
            }

            $uri = trim($listing->path(), '/');
            if ($uri === '') {
                continue;
            }

            $dirname = pathinfo($uri, PATHINFO_DIRNAME);
            if ($dirname === '.') {
                $dirname = '';
            }

            yield new FsListing([
                'dirname' => $dirname,
                'basename' => pathinfo($uri, PATHINFO_BASENAME),
                'type' => $listing->isDir() ? 'dir' : 'file',
                'dateModified' => $listing->lastModified(),
                'fileSize' => !$listing->isDir() && method_exists($listing, 'fileSize') ? $listing->fileSize() : null,
            ]);
        }
    }

    /**
     * @inheritdoc
     */
    public function getFileSize(string $uri): int
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($uri);

        try {
            return $this->storageDisk()->size($path);
        } catch (Throwable $e) {
            throw new FsException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getDateModified(string $uri): int
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($uri);

        try {
            return $this->storageDisk()->lastModified($path);
        } catch (Throwable $e) {
            throw new FsException($e->getMessage(), previous: $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($path);

        if (!is_resource($stream)) {
            throw new FsException("Unable to write stream to path: $path");
        }

        $directory = pathinfo($path, PATHINFO_DIRNAME);
        if ($directory !== '.' && $directory !== '') {
            $this->createDirectory($directory, $config);
        }

        if (!$this->storageDisk()->writeStream($path, $stream, $this->legacyConfigForDisk($config, self::VISIBILITY_FILE))) {
            throw new FsException("Unable to write stream to path: $path");
        }
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($path);

        try {
            $contents = $this->storageDisk()->get($path);
        } catch (Throwable $e) {
            throw new FsException($e->getMessage(), previous: $e);
        }

        if ($contents === null) {
            throw new FsObjectNotFoundException("Unable to read file at path: $path");
        }

        return $contents;
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($path);

        if (!$this->storageDisk()->put($path, $contents, $this->legacyConfigForDisk($config, self::VISIBILITY_FILE))) {
            throw new FsException("Unable to write file at path: $path");
        }
    }

    /**
     * @inheritdoc
     */
    public function fileExists(string $path): bool
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        try {
            return $this->storageDisk()->exists($this->normalizeDiskPath($path));
        } catch (FsException $exception) {
            Craft::$app->getErrorHandler()->logException($exception);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function deleteFile(string $path): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($path);

        if (!$this->fileExists($path)) {
            return;
        }

        if (!$this->storageDisk()->delete($path)) {
            Log::info("Tried to delete `$path`, but could not.");
        }
    }

    /**
     * @inheritdoc
     */
    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($path);
        $newPath = $this->normalizeDiskPath($newPath);

        $directory = pathinfo($newPath, PATHINFO_DIRNAME);
        if ($directory !== '.' && $directory !== '') {
            $this->createDirectory($directory, $config);
        }

        if (!$this->storageDisk()->move($path, $newPath)) {
            Log::info("Tried to move `$path` to `$newPath`, but could not.");
        }
    }

    /**
     * @inheritdoc
     */
    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($path);
        $newPath = $this->normalizeDiskPath($newPath);

        $directory = pathinfo($newPath, PATHINFO_DIRNAME);
        if ($directory !== '.' && $directory !== '') {
            $this->createDirectory($directory, $config);
        }

        if (!$this->storageDisk()->copy($path, $newPath)) {
            Log::info("Tried to copy `$path` to `$newPath`, but could not.");
        }
    }

    /**
     * @inheritdoc
     */
    public function getFileStream(string $uriPath)
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($uriPath);
        $stream = $this->storageDisk()->readStream($path);
        if (!is_resource($stream)) {
            throw new FsObjectNotFoundException("Unable to open $path.");
        }

        return $stream;
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        return $this->storageDisk()->directoryExists($this->normalizeDiskPath($path));
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $path = $this->normalizeDiskPath($path);

        if ($path === '') {
            return;
        }

        if (!$this->storageDisk()->makeDirectory($path)) {
            throw new FsException("Unable to create directory at path: $path");
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);
        $this->storageDisk()->deleteDirectory($this->normalizeDiskPath($path));
    }

    /**
     * @inheritdoc
     */
    public function renameDirectory(string $path, string $newName): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        $path = $this->normalizeDiskPath($path);
        if ($path === '' || !$this->directoryExists($path)) {
            throw new FsObjectNotFoundException('No folder exists at path: ' . $path);
        }

        $newName = trim($newName, '/');
        if ($newName === '') {
            throw new FsException('New directory name cannot be empty.');
        }

        $parentPath = pathinfo($path, PATHINFO_DIRNAME);
        if ($parentPath === '.') {
            $parentPath = '';
        }

        $newPath = ($parentPath !== '' ? "$parentPath/" : '') . $this->normalizeDiskPath($newName);
        if ($newPath === $path) {
            return;
        }

        $disk = $this->storageDisk();

        if (!$disk->makeDirectory($newPath)) {
            throw new FsException("Unable to create directory at path: $newPath");
        }

        $directories = $disk->allDirectories($path);
        usort($directories, fn(string $a, string $b) => substr_count($a, '/') <=> substr_count($b, '/'));

        foreach ($directories as $directory) {
            $targetDirectory = $this->swapDirectoryPrefix($directory, $path, $newPath);
            if (!$disk->makeDirectory($targetDirectory)) {
                throw new FsException("Unable to create directory at path: $targetDirectory");
            }
        }

        foreach ($disk->allFiles($path) as $file) {
            $targetFile = $this->swapDirectoryPrefix($file, $path, $newPath);
            if (!$disk->move($file, $targetFile)) {
                throw new FsException("Unable to move $file to $targetFile");
            }
        }

        $disk->deleteDirectory($path);
    }

    /**
     * Prefix the path with the root path.
     *
     * @param string $path
     * @return string
     * @throws FsException if path is not contained.
     */
    protected function prefixPath(string $path = ''): string
    {
        if (!Path::ensurePathIsContained($path)) {
            throw new FsException("The path `$path` is not contained.");
        }

        return $this->getRootPath() . DIRECTORY_SEPARATOR . FileHelper::normalizePath($path);
    }

    private function storageDisk(): LaravelFilesystem
    {
        $disk = Storage::build($this->getDiskConfig());

        if (!$disk instanceof LaravelFilesystem) {
            throw new FsException('Invalid Laravel disk configuration.');
        }

        return $disk;
    }

    /**
     * @param  array<string,mixed>  $config
     * @return array<string,mixed>
     */
    private function legacyConfigForDisk(array $config, string $type): array
    {
        $visibility = $this->diskVisibility($type, $config);

        return $visibility !== null ? ['visibility' => $visibility] : [];
    }

    /**
     * @param  array<string,mixed>  $config
     */
    private function diskVisibility(string $type, array $config): ?string
    {
        if (!empty($config[self::CONFIG_VISIBILITY])) {
            return $config[self::CONFIG_VISIBILITY] === self::VISIBILITY_HIDDEN ? 'private' : 'public';
        }

        return $this->defaultDiskVisibility($type);
    }

    private function defaultDiskVisibility(string $type): string
    {
        $defaultMode = $this->visibilityMap[$type][self::VISIBILITY_DEFAULT];
        $hiddenMode = $this->visibilityMap[$type][self::VISIBILITY_HIDDEN];

        return $defaultMode === $hiddenMode ? 'private' : 'public';
    }

    private function normalizeDiskPath(string $path = ''): string
    {
        $path = trim(FileHelper::normalizePath($path), '/');
        $path = $path === '.' ? '' : $path;

        if (!Path::ensurePathIsContained($path)) {
            throw new FsException("The path `$path` is not contained.");
        }

        return $path;
    }

    private function swapDirectoryPrefix(string $path, string $prefix, string $replacement): string
    {
        $prefix = trim($prefix, '/');
        $replacement = trim($replacement, '/');

        return preg_replace(
            '/^' . preg_quote($prefix, '/') . '(?=\/|$)/',
            $replacement,
            trim($path, '/'),
            1,
        ) ?? trim($path, '/');
    }

    private function logLegacyOperationDeprecation(string $method): void
    {
        $methodName = str_contains($method, '::') ? explode('::', $method)[1] : $method;

        Deprecator::log(
            sprintf('filesystem-legacy-operation:%s::%s', static::class, $methodName),
            sprintf('Calling `%s::%s()` is deprecated. Use Laravel disk operations instead.', static::class, $methodName),
        );
    }

    /**
     * Resolve visibility by a config array and type.
     *
     * @param string $type
     * @param array $config
     * @return int
     */
    protected function resolveVisibility(string $type, array $config = []): int
    {
        if (empty($config[self::CONFIG_VISIBILITY])) {
            return $this->visibilityMap[$type][self::VISIBILITY_DEFAULT];
        }

        return $this->visibilityMap[$type][$config[self::CONFIG_VISIBILITY]];
    }
}
