<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Filesystem\Exceptions\FsObjectNotFoundException;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem as FilesystemComponent;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use Generator;
use Illuminate\Contracts\Filesystem\Filesystem;
use League\Flysystem\StorageAttributes;
use Throwable;

class VolumeMixin
{
    public function canGetProperty(): Closure
    {
        return function($name, $checkVars = true, $checkBehaviors = true): bool {
            if ($checkVars && property_exists($this, (string) $name)) {
                return true;
            }

            if (method_exists($this, 'get' . $name)) {
                return true;
            }

            if (method_exists($this, 'get' . ucfirst((string) $name))) {
                return true;
            }

            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            if ($this::hasMacro('get' . $name)) {
                return true;
            }

            return $this::hasMacro('get' . ucfirst((string) $name));
        };
    }

    public function canSetProperty(): Closure
    {
        return function($name, $checkVars = true, $checkBehaviors = true): bool {
            if ($checkVars && property_exists($this, (string) $name)) {
                return true;
            }

            if (method_exists($this, 'set' . $name)) {
                return true;
            }

            if (method_exists($this, 'set' . ucfirst((string) $name))) {
                return true;
            }

            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            if ($this::hasMacro('set' . $name)) {
                return true;
            }

            return $this::hasMacro('set' . ucfirst((string) $name));
        };
    }

    public function getRootUrl(): Closure
    {
        return function(): string {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            $rootUrl = $this->getFs()->getRootUrl() ?? '';
            if ($rootUrl !== '') {
                $rootUrl = Str::finish($rootUrl, '/');
            }

            return $rootUrl . $this->getSubpath();
        };
    }

    public function getFileList(): Closure
    {
        return function(string $directory = '', bool $recursive = true): Generator {
            $targetDirectory = trim($directory, '/');
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
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
                /**
                 * @var Volume $this
                 *
                 * @phpstan-ignore-next-line
                 */
                return $this->sourceDisk()->size($uri);
            } catch (Throwable $e) {
                throw new FilesystemException($e->getMessage(), previous: $e);
            }
        };
    }

    public function getDateModified(): Closure
    {
        return function(string $uri): int {
            try {
                /**
                 * @var Volume $this
                 *
                 * @phpstan-ignore-next-line
                 */
                return $this->sourceDisk()->lastModified($uri);
            } catch (Throwable $e) {
                throw new FilesystemException($e->getMessage(), previous: $e);
            }
        };
    }

    public function write(): Closure
    {
        $mixin = $this;

        return function(string $path, string $contents, array $config = []) use ($mixin): void {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            if (!$this->sourceDisk()->put($path, $contents, $mixin->legacyConfigForDisk($config))) {
                throw new FilesystemException("Unable to write file at path: $path");
            }
        };
    }

    public function read(): Closure
    {
        $mixin = $this;

        return function(string $path) use ($mixin): string {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
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

        return function(string $path, $stream, array $config = []) use ($mixin): void {
            if (!is_resource($stream)) {
                throw new FilesystemException("Unable to write stream to path: $path");
            }

            try {
                /**
                 * @var Volume $this
                 *
                 * @phpstan-ignore-next-line
                 */
                if (!$this->sourceDisk()->writeStream($path, $stream, $mixin->legacyConfigForDisk($config))) {
                    throw new FilesystemException("Unable to write stream to path: $path");
                }
            } catch (Throwable $e) {
                throw new FilesystemException($e->getMessage(), previous: $e);
            }
        };
    }

    public function fileExists(): Closure
    {
        return function(string $path) {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            return $this->sourceDisk()->exists($path);
        };
    }

    public function deleteFile(): Closure
    {
        return function(string $path): void {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            $this->sourceDisk()->delete($path);
        };
    }

    public function renameFile(): Closure
    {
        return function(string $path, string $newPath, array $config = []): void {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            if (!$this->sourceDisk()->move($path, $newPath)) {
                throw new FilesystemException("Unable to move $path to $newPath");
            }
        };
    }

    public function copyFile(): Closure
    {
        return function(string $path, string $newPath, array $config = []): void {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            if (!$this->sourceDisk()->copy($path, $newPath)) {
                throw new FilesystemException("Unable to copy $path to $newPath");
            }
        };
    }

    public function getFileStream(): Closure
    {
        $mixin = $this;

        return function(string $uriPath) use ($mixin) {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
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
        return function(string $path): bool {
            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            return $this->sourceDisk()->directoryExists(trim($path, '/'));
        };
    }

    public function createDirectory(): Closure
    {
        $mixin = $this;

        return function(string $path, array $config = []) use ($mixin): void {
            $path = trim($path, '/');
            if ($path === '') {
                return;
            }

            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            if (!$this->sourceDisk()->makeDirectory($path, $mixin->legacyConfigForDisk($config))) {
                throw new FilesystemException("Unable to create directory at path: $path");
            }
        };
    }

    public function deleteDirectory(): Closure
    {
        return function(string $path = ''): bool {
            $directory = trim($path, '/');

            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            if ($directory === '' && $this->getSubpath(false) === '') {
                return false;
            }

            return $this->sourceDisk()->deleteDirectory($directory);
        };
    }

    public function renameDirectory(): Closure
    {
        $mixin = $this;

        return function(string $path, string $newName) use ($mixin): void {
            $sourcePath = trim($path, '/');

            /**
             * @var Volume $this
             *
             * @phpstan-ignore-next-line
             */
            $disk = $this->sourceDisk();

            if ($sourcePath === '' || !$disk->directoryExists($sourcePath)) {
                throw new FsObjectNotFoundException("No folder exists at path: $path");
            }

            $newName = trim($newName, '/');
            if ($newName === '') {
                throw new FilesystemException('New directory name cannot be empty.');
            }

            $parentPath = pathinfo($sourcePath, PATHINFO_DIRNAME);
            if ($parentPath === '.') {
                $parentPath = '';
            }

            $targetPath = $newName;
            if ($parentPath !== '') {
                $targetPath = "$parentPath/$newName";
            }
            if ($targetPath === $sourcePath) {
                return;
            }

            if (!$disk->makeDirectory($targetPath)) {
                throw new FilesystemException("Unable to create directory at path: $targetPath");
            }

            $directories = $disk->allDirectories($sourcePath);
            usort($directories, fn(string $a, string $b) => substr_count($a, '/') <=> substr_count($b, '/'));

            foreach ($directories as $directory) {
                $targetDirectory = $mixin->swapPathPrefix($directory, $sourcePath, $targetPath);

                if (!$disk->makeDirectory($targetDirectory)) {
                    throw new FilesystemException("Unable to create directory at path: $targetDirectory");
                }
            }

            foreach ($disk->allFiles($sourcePath) as $file) {
                $targetFile = $mixin->swapPathPrefix($file, $sourcePath, $targetPath);

                if (!$disk->move($file, $targetFile)) {
                    throw new FilesystemException("Unable to move $file to $targetFile");
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

        $visibility = Arr::pull($config, FilesystemComponent::CONFIG_VISIBILITY);

        if ($visibility === FilesystemComponent::VISIBILITY_HIDDEN) {
            $config['visibility'] = 'private';
        }

        return $config;
    }

    private function readException(Filesystem $disk, string $path, Throwable $exception): FilesystemException
    {
        try {
            if (!$disk->exists($path)) {
                return new FsObjectNotFoundException("Unable to read file at path: $path", previous: $exception);
            }
        } catch (Throwable) {
            // Fall through to a generic filesystem exception.
        }

        return new FilesystemException($exception->getMessage(), previous: $exception);
    }

    private function swapPathPrefix(string $path, string $sourcePath, string $targetPath): string
    {
        return preg_replace(
            '/^' . preg_quote($sourcePath, '/') . '(?=\/|$)/',
            $targetPath,
            trim($path, '/'),
            1,
        ) ?? trim($path, '/');
    }
}
