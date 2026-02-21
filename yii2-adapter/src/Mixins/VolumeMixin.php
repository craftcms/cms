<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use craft\models\FsListing;
use CraftCms\Cms\Support\Str;
use Generator;
use League\Flysystem\StorageAttributes;
use Throwable;

final class VolumeMixin
{
    public function getRootUrl(): Closure
    {
        return function(): ?string {
            $rootUrl = $this->getFs()->getRootUrl() ?? '';

            return ($rootUrl !== '' ? Str::finish($rootUrl, '/') : '') . $this->getSubpath();
        };
    }

    public function getFileList(): Closure
    {
        return function(string $directory = '', bool $recursive = true): Generator {
            $targetDirectory = trim($directory, '/');

            foreach ($this->storageDisk()->listContents($targetDirectory, $recursive) as $item) {
                if (!$item instanceof StorageAttributes) {
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
                    'fileSize' => !$item->isDir() && method_exists($item, 'fileSize') ? $item->fileSize() : null,
                ]);
            }
        };
    }

    public function getFileSize(): Closure
    {
        return fn(string $uri): int => $this->size($uri);
    }

    public function getDateModified(): Closure
    {
        return fn(string $uri): int => $this->lastModified($uri);
    }

    public function write(): Closure
    {
        return function(string $path, string $contents, array $config = []): void {
            if (!$this->put($path, $contents, $config)) {
                throw new FsException("Unable to write file at path: $path");
            }
        };
    }

    public function read(): Closure
    {
        return function(string $path): string {
            try {
                $contents = $this->get($path);
            } catch (Throwable $e) {
                throw new FsException($e->getMessage(), previous: $e);
            }

            if ($contents === null) {
                throw new FsObjectNotFoundException("Unable to read file at path: $path");
            }

            return $contents;
        };
    }

    public function writeFileFromStream(): Closure
    {
        return function(string $path, $stream, array $config = []): void {
            if (!is_resource($stream) || !$this->writeStream($path, $stream, $config)) {
                throw new FsException("Unable to write stream to path: $path");
            }
        };
    }

    public function fileExists(): Closure
    {
        return fn(string $path): bool => $this->exists($path);
    }

    public function deleteFile(): Closure
    {
        return function(string $path): void {
            $this->delete($path);
        };
    }

    public function renameFile(): Closure
    {
        return function(string $path, string $newPath, array $config = []): void {
            if (!$this->move($path, $newPath)) {
                throw new FsException("Unable to move $path to $newPath");
            }
        };
    }

    public function copyFile(): Closure
    {
        return function(string $path, string $newPath, array $config = []): void {
            if (!$this->copy($path, $newPath)) {
                throw new FsException("Unable to copy $path to $newPath");
            }
        };
    }

    public function getFileStream(): Closure
    {
        return function(string $uriPath) {
            $stream = $this->readStream($uriPath);

            if (!is_resource($stream)) {
                throw new FsObjectNotFoundException("Unable to open $uriPath.");
            }

            return $stream;
        };
    }

    public function directoryExists(): Closure
    {
        return fn(string $path): bool => $this->storageDisk()->directoryExists(trim($path, '/'));
    }

    public function createDirectory(): Closure
    {
        return function(string $path, array $config = []): void {
            if (!$this->makeDirectory($path)) {
                throw new FsException("Unable to create directory at path: $path");
            }
        };
    }

    public function renameDirectory(): Closure
    {
        return function(string $path, string $newName): void {
            $sourcePath = trim($path, '/');
            if ($sourcePath === '' || !$this->storageDisk()->directoryExists($sourcePath)) {
                throw new FsObjectNotFoundException("No folder exists at path: $path");
            }

            $newName = trim($newName, '/');
            if ($newName === '') {
                throw new FsException('New directory name cannot be empty.');
            }

            $parentPath = pathinfo($sourcePath, PATHINFO_DIRNAME);
            if ($parentPath === '.') {
                $parentPath = '';
            }

            $targetPath = ($parentPath !== '' ? "$parentPath/" : '') . $newName;
            if ($targetPath === $sourcePath) {
                return;
            }

            $disk = $this->storageDisk();

            if (!$disk->makeDirectory($targetPath)) {
                throw new FsException("Unable to create directory at path: $targetPath");
            }

            $directories = $disk->allDirectories($sourcePath);
            usort($directories, fn(string $a, string $b) => substr_count($a, '/') <=> substr_count($b, '/'));

            foreach ($directories as $directory) {
                $targetDirectory = $this->swapDirectoryPrefix($directory, $sourcePath, $targetPath);
                if (!$disk->makeDirectory($targetDirectory)) {
                    throw new FsException("Unable to create directory at path: $targetDirectory");
                }
            }

            foreach ($disk->allFiles($sourcePath) as $file) {
                $targetFile = $this->swapDirectoryPrefix($file, $sourcePath, $targetPath);
                if (!$disk->move($file, $targetFile)) {
                    throw new FsException("Unable to move $file to $targetFile");
                }
            }

            $disk->deleteDirectory($sourcePath);
        };
    }
}
