<?php

declare(strict_types=1);

namespace CraftCms\Cms\Filesystem\Filesystems;

use craft\base\Fs;
use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use craft\models\FsListing;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Str;
use Generator;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystem;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\StorageAttributes;
use Override;
use Throwable;

use function CraftCms\Cms\t;

/**
 * DiskFileSystem represents a Laravel-configured filesystem disk.
 */
class DiskFilesystem extends Fs
{
    /**
     * {@inheritdoc}
     */
    protected static bool $showHasUrlSetting = false;

    /**
     * {@inheritdoc}
     */
    protected static bool $showUrlSetting = false;

    /**
     * @var string|null The Laravel disk name.
     */
    public ?string $disk = null;

    /**
     * {@inheritdoc}
     */
    #[Override]
    public static function displayName(): string
    {
        return t('Laravel Disk');
    }

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        if (isset($config['disk']) && is_string($config['disk'])) {
            $disk = $config['disk'];
            $config['name'] ??= $disk;
            $config['handle'] ??= "disk:$disk";
        }

        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function getRootUrl(): ?string
    {
        if (! $this->disk) {
            return null;
        }

        $url = config("filesystems.disks.$this->disk.url");
        if (! is_string($url) || $url === '') {
            return null;
        }

        return Str::finish($url, '/');
    }

    #[Override]
    public function getDiskConfig(): array
    {
        if (! $this->disk) {
            throw new FsException('The Laravel disk name is missing.');
        }

        $diskConfig = config("filesystems.disks.$this->disk");
        if (! is_array($diskConfig)) {
            throw new FsException("Invalid Laravel disk configuration for [$this->disk].");
        }

        return $diskConfig;
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['disk'], 'required'];

        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        foreach ($this->storageDisk()->listContents($directory, $recursive) as $item) {
            if (! $item instanceof StorageAttributes) {
                continue;
            }

            $uri = trim($item->path(), '/');
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
                'type' => $item->isDir() ? 'dir' : 'file',
                'dateModified' => $item->lastModified(),
                'fileSize' => ! $item->isDir() && method_exists($item, 'fileSize') ? $item->fileSize() : null,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFileSize(string $uri): int
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        try {
            return $this->storageDisk()->size($uri);
        } catch (Throwable $e) {
            throw new FsException($e->getMessage(), previous: $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDateModified(string $uri): int
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        try {
            return $this->storageDisk()->lastModified($uri);
        } catch (Throwable $e) {
            throw new FsException($e->getMessage(), previous: $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $path, string $contents, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        if (! $this->storageDisk()->put($path, $contents, $config)) {
            throw new FsException("Unable to write file at path: $path");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $path): string
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

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
     * {@inheritdoc}
     */
    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        if (! is_resource($stream) || ! $this->storageDisk()->writeStream($path, $stream, $config)) {
            throw new FsException("Unable to write stream to path: $path");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists(string $path): bool
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        return $this->storageDisk()->exists($path);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFile(string $path): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        $this->storageDisk()->delete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        if (! $this->storageDisk()->move($path, $newPath)) {
            throw new FsException("Unable to move $path to $newPath");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        if (! $this->storageDisk()->copy($path, $newPath)) {
            throw new FsException("Unable to copy $path to $newPath");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFileStream(string $uriPath)
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        $stream = $this->storageDisk()->readStream($uriPath);
        if (! is_resource($stream)) {
            throw new FsObjectNotFoundException("Unable to open $uriPath.");
        }

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function directoryExists(string $path): bool
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        return $this->storageDisk()->directoryExists($path);
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectory(string $path, array $config = []): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        if (! $this->storageDisk()->makeDirectory($path)) {
            throw new FsException("Unable to create directory at path: $path");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDirectory(string $path): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        $this->storageDisk()->deleteDirectory($path);
    }

    /**
     * {@inheritdoc}
     */
    public function renameDirectory(string $path, string $newName): void
    {
        $this->logLegacyOperationDeprecation(__METHOD__);

        $path = trim($path, '/');
        if ($path === '' || ! $this->directoryExists($path)) {
            throw new FsObjectNotFoundException("No folder exists at path: $path");
        }

        $newName = trim($newName, '/');
        if ($newName === '') {
            throw new FsException('New directory name cannot be empty.');
        }

        $parentPath = pathinfo($path, PATHINFO_DIRNAME);
        if ($parentPath === '.') {
            $parentPath = '';
        }

        $newPath = ($parentPath !== '' ? "$parentPath/" : '').$newName;
        if ($newPath === $path) {
            return;
        }

        $disk = $this->storageDisk();
        if (! $disk->makeDirectory($newPath)) {
            throw new FsException("Unable to create directory at path: $newPath");
        }

        $directories = $disk->allDirectories($path);
        usort($directories, fn (string $a, string $b): int => substr_count($a, '/') <=> substr_count($b, '/'));

        foreach ($directories as $directory) {
            $targetDirectory = $this->swapDirectoryPrefix($directory, $path, $newPath);
            if (! $disk->makeDirectory($targetDirectory)) {
                throw new FsException("Unable to create directory at path: $targetDirectory");
            }
        }

        foreach ($disk->allFiles($path) as $file) {
            $targetFile = $this->swapDirectoryPrefix($file, $path, $newPath);
            if (! $disk->move($file, $targetFile)) {
                throw new FsException("Unable to move $file to $targetFile");
            }
        }

        $disk->deleteDirectory($path);
    }

    private function storageDisk(): LaravelFilesystem
    {
        if (! $this->disk) {
            throw new FsException('The Laravel disk name is missing.');
        }

        /** @var LaravelFilesystem $disk */
        $disk = Storage::disk($this->disk);

        return $disk;
    }

    private function swapDirectoryPrefix(string $path, string $prefix, string $replacement): string
    {
        $prefix = trim($prefix, '/');
        $replacement = trim($replacement, '/');

        return preg_replace(
            '/^'.preg_quote($prefix, '/').'(?=\/|$)/',
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
            sprintf(
                'Calling `%s::%s()` is deprecated. Use Laravel disk operations instead.',
                static::class,
                $methodName,
            ),
        );
    }
}
