<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use craft\errors\FsException;
use craft\errors\FsObjectNotFoundException;
use craft\models\FsListing;
use CraftCms\Cms\Filesystem\Filesystem as FilesystemComponent;
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
            $disk = $this->sourceDisk();

            foreach ($disk->listContents($targetDirectory, $recursive) as $item) {
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
        return function(string $uri): int {
            try {
                return $this->sourceDisk()->size($uri);
            } catch (Throwable $e) {
                throw new FsException($e->getMessage(), previous: $e);
            }
        };
    }

    public function getDateModified(): Closure
    {
        return function(string $uri): int {
            try {
                return $this->sourceDisk()->lastModified($uri);
            } catch (Throwable $e) {
                throw new FsException($e->getMessage(), previous: $e);
            }
        };
    }

    public function write(): Closure
    {
        $mixin = $this;

        return function(string $path, string $contents, array $config = []): void {
            if (!$this->sourceDisk()->put($path, $contents, $mixin->legacyConfigForDisk($config))) {
                throw new FsException("Unable to write file at path: $path");
            }
        };
    }

    public function read(): Closure
    {
        $mixin = $this;

        return function(string $path): string {
            $disk = $this->sourceDisk();

            try {
                $contents = $disk->get($path);
            } catch (Throwable $e) {
                throw $mixin->readException($disk, $path, $e);
            }

            if ($contents === null) {
                throw new FsObjectNotFoundException("Unable to read file at path: $path");
            }

            return $contents;
        };
    }

    public function writeFileFromStream(): Closure
    {
        $mixin = $this;

        return function(string $path, $stream, array $config = []): void {
            if (!is_resource($stream)) {
                throw new FsException("Unable to write stream to path: $path");
            }

            try {
                if (!$this->sourceDisk()->writeStream($path, $stream, $mixin->legacyConfigForDisk($config))) {
                    throw new FsException("Unable to write stream to path: $path");
                }
            } catch (Throwable $e) {
                throw new FsException($e->getMessage(), previous: $e);
            }
        };
    }

    public function fileExists(): Closure
    {
        return fn(string $path): bool => $this->sourceDisk()->exists($path);
    }

    public function deleteFile(): Closure
    {
        return function(string $path): void {
            $this->sourceDisk()->delete($path);
        };
    }

    public function renameFile(): Closure
    {
        return function(string $path, string $newPath, array $config = []): void {
            if (!$this->sourceDisk()->move($path, $newPath)) {
                throw new FsException("Unable to move $path to $newPath");
            }
        };
    }

    public function copyFile(): Closure
    {
        return function(string $path, string $newPath, array $config = []): void {
            if (!$this->sourceDisk()->copy($path, $newPath)) {
                throw new FsException("Unable to copy $path to $newPath");
            }
        };
    }

    public function getFileStream(): Closure
    {
        $mixin = $this;

        return function(string $uriPath) {
            $disk = $this->sourceDisk();

            try {
                $stream = $disk->readStream($uriPath);
            } catch (Throwable $e) {
                throw $mixin->readException($disk, $uriPath, $e);
            }

            if (!is_resource($stream)) {
                throw new FsObjectNotFoundException("Unable to open $uriPath.");
            }

            return $stream;
        };
    }

    public function directoryExists(): Closure
    {
        return fn(string $path): bool => $this->sourceDisk()->directoryExists(trim($path, '/'));
    }

    public function createDirectory(): Closure
    {
        $mixin = $this;

        return function(string $path, array $config = []): void {
            $path = trim($path, '/');
            if ($path === '') {
                return;
            }

            if (!$this->sourceDisk()->makeDirectory($path, $mixin->legacyConfigForDisk($config))) {
                throw new FsException("Unable to create directory at path: $path");
            }
        };
    }

    public function deleteDirectory(): Closure
    {
        return function(string $path = ''): bool {
            $directory = trim($path, '/');

            if ($directory === '' && $this->getSubpath(false) === '') {
                return false;
            }

            return $this->sourceDisk()->deleteDirectory($directory);
        };
    }

    public function renameDirectory(): Closure
    {
        return function(string $path, string $newName): void {
            $sourcePath = trim($path, '/');
            $disk = $this->sourceDisk();

            if ($sourcePath === '' || !$disk->directoryExists($sourcePath)) {
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

            if (!$disk->makeDirectory($targetPath)) {
                throw new FsException("Unable to create directory at path: $targetPath");
            }

            $directories = $disk->allDirectories($sourcePath);
            usort($directories, fn(string $a, string $b) => substr_count($a, '/') <=> substr_count($b, '/'));

            foreach ($directories as $directory) {
                $targetDirectory = preg_replace(
                    '/^' . preg_quote($sourcePath, '/') . '(?=\/|$)/',
                    $targetPath,
                    trim($directory, '/'),
                    1,
                ) ?? trim($directory, '/');

                if (!$disk->makeDirectory($targetDirectory)) {
                    throw new FsException("Unable to create directory at path: $targetDirectory");
                }
            }

            foreach ($disk->allFiles($sourcePath) as $file) {
                $targetFile = preg_replace(
                    '/^' . preg_quote($sourcePath, '/') . '(?=\/|$)/',
                    $targetPath,
                    trim($file, '/'),
                    1,
                ) ?? trim($file, '/');

                if (!$disk->move($file, $targetFile)) {
                    throw new FsException("Unable to move $file to $targetFile");
                }
            }

            $disk->deleteDirectory($sourcePath);
        };
    }

    /**
     * @param  array<string,mixed>  $config
     * @return array<string,mixed>
     */
    private function legacyConfigForDisk(array $config): array
    {
        if (empty($config[FilesystemComponent::CONFIG_VISIBILITY])) {
            return $config;
        }

        $config['visibility'] = $config[FilesystemComponent::CONFIG_VISIBILITY] === FilesystemComponent::VISIBILITY_HIDDEN
            ? 'private'
            : 'public';
        unset($config[FilesystemComponent::CONFIG_VISIBILITY]);

        return $config;
    }

    private function readException(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $path, Throwable $exception): FsException
    {
        try {
            if (!$disk->exists($path)) {
                return new FsObjectNotFoundException("Unable to read file at path: $path", previous: $exception);
            }
        } catch (Throwable) {
            // Fall through to a generic filesystem exception.
        }

        return new FsException($exception->getMessage(), previous: $exception);
    }
}
