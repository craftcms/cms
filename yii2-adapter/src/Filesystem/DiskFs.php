<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Filesystem;

use craft\base\Fs;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Support\Str;
use Generator;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Override;

/**
 * Adapts a Laravel disk to Craft's deprecated Yii filesystem API.
 *
 * @internal
 */
class DiskFs extends Fs
{
    public ?string $disk = null;

    public function __construct($config = [])
    {
        if (isset($config['disk']) && is_string($config['disk'])) {
            $config['name'] ??= $config['disk'];
            $config['handle'] ??= $config['disk'];
        }

        parent::__construct($config);
    }

    public static function displayName(): string
    {
        return 'Laravel Disk';
    }

    #[Override]
    public function getRootUrl(): ?string
    {
        $url = $this->disk ? config("filesystems.disks.$this->disk.url") : null;

        return is_string($url) && $url !== '' ? Str::finish($url, '/') : null;
    }

    #[Override]
    public function getDiskConfig(): array
    {
        $config = $this->disk ? config("filesystems.disks.$this->disk") : null;
        if (!is_array($config)) {
            throw new FilesystemException('The Laravel disk configuration is missing.');
        }

        return $config;
    }

    public function getFileList(string $directory = '', bool $recursive = true): Generator
    {
        foreach ($this->adapter()->listContents($directory, $recursive) as $item) {
            yield new FsListing([
                'dirname' => dirname($item->path()) === '.' ? '' : dirname($item->path()),
                'basename' => basename($item->path()),
                'type' => $item->isDir() ? 'dir' : 'file',
                'fileSize' => $item->isFile() ? $this->adapter()->size($item->path()) : null,
                'dateModified' => $item->lastModified(),
            ]);
        }
    }

    public function getFileSize(string $uri): int
    {
        return $this->adapter()->size($uri);
    }

    public function getDateModified(string $uri): int
    {
        return $this->adapter()->lastModified($uri);
    }

    public function write(string $path, string $contents, array $config = []): void
    {
        $this->adapter()->put($path, $contents, $config);
    }

    public function read(string $path): string
    {
        return $this->adapter()->get($path);
    }

    public function writeFileFromStream(string $path, $stream, array $config = []): void
    {
        $this->adapter()->writeStream($path, $stream, $config);
    }

    public function fileExists(string $path): bool
    {
        return $this->adapter()->fileExists($path);
    }

    public function deleteFile(string $path): void
    {
        $this->adapter()->delete($path);
    }

    public function renameFile(string $path, string $newPath, array $config = []): void
    {
        $this->adapter()->move($path, $newPath);
    }

    public function copyFile(string $path, string $newPath, array $config = []): void
    {
        $this->adapter()->copy($path, $newPath);
    }

    public function getFileStream(string $uriPath)
    {
        return $this->adapter()->readStream($uriPath);
    }

    public function directoryExists(string $path): bool
    {
        return $this->adapter()->directoryExists($path);
    }

    public function createDirectory(string $path, array $config = []): void
    {
        $this->adapter()->makeDirectory($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->adapter()->deleteDirectory($path);
    }

    public function renameDirectory(string $path, string $newName): void
    {
        $parent = dirname($path);
        $target = ($parent === '.' ? '' : "$parent/") . $newName;

        foreach ($this->adapter()->allFiles($path) as $file) {
            $this->adapter()->move($file, $target . substr($file, strlen($path)));
        }

        $this->adapter()->deleteDirectory($path);
    }

    protected function adapter(): FilesystemAdapter
    {
        if ($this->disk) {
            return Storage::disk($this->disk);
        }

        $adapter = Storage::build($this->getDiskConfig());
        if (!$adapter instanceof FilesystemAdapter) {
            throw new FilesystemException('The Laravel disk did not resolve to a filesystem adapter.');
        }

        return $adapter;
    }
}
