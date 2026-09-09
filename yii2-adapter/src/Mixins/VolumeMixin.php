<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Filesystem\Exceptions\FsObjectNotFoundException;
use CraftCms\Cms\Filesystem\Filesystems\Filesystem as FilesystemComponent;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Str;
use CraftCms\Yii2Adapter\Asset\LegacyVolumeTransformData;
use Generator;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use League\Flysystem\StorageAttributes;
use RuntimeException;
use Throwable;

class VolumeMixin
{
    private LegacyVolumeTransformData $transformData;

    public function __construct()
    {
        $this->transformData = new LegacyVolumeTransformData();
    }

    public function getTransformFs(): Closure
    {
        $data = $this->transformData;

        return function() use ($data): FsInterface {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            $handle = $data->get($this)->filesystem;
            if (!$handle) {
                return $this->getFs();
            }

            $filesystem = Filesystems::resolve($handle);
            if ($filesystem) {
                return $filesystem;
            }

            Log::error("Invalid transform filesystem handle: {$handle} for the {$this->name} volume.");

            return new \CraftCms\Cms\Filesystem\Filesystems\MissingFs(['handle' => $handle]);
        };
    }

    public function setTransformFs(): Closure
    {
        $data = $this->transformData;

        return function(FsInterface|string|null $filesystem) use ($data): void {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            $handle = $filesystem instanceof FsInterface ? $filesystem->handle : $filesystem;
            $data->setFilesystem($this, $handle);
        };
    }

    public function getTransformFsHandle(): Closure
    {
        $data = $this->transformData;

        return function(bool $parse = true) use ($data): ?string {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            $handle = $data->get($this)->filesystem;

            return $parse ? Env::parse($handle) : $handle;
        };
    }

    public function setTransformFsHandle(): Closure
    {
        $data = $this->transformData;

        return function(?string $handle) use ($data): void {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            $data->setFilesystem($this, $handle);
        };
    }

    public function getResolvedTransformFsTarget(): Closure
    {
        $data = $this->transformData;

        return function(bool $parse = true) use ($data): ?string {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            return $this->resolveStorageTargetKey($data->get($this)->filesystem, $parse);
        };
    }

    public function getTransformSubpath(): Closure
    {
        $data = $this->transformData;

        return function(bool $ensureTrailing = true, bool $parse = true) use ($data): string {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            $subpath = $data->get($this)->subpath;
            $subpath = $parse ? (Env::parse($subpath) ?? '') : $subpath;

            return $ensureTrailing && $subpath !== '' ? Str::finish($subpath, '/') : $subpath;
        };
    }

    public function setTransformSubpath(): Closure
    {
        $data = $this->transformData;

        return function(?string $subpath) use ($data): void {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            $data->setSubpath($this, $subpath ?? '');
        };
    }

    public function transformDisk(): Closure
    {
        $data = $this->transformData;

        return function() use ($data): FilesystemAdapter {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            $values = $data->get($this);
            $target = $this->resolveStorageTargetKey($values->filesystem ?: $this->getFsHandle(false));
            if ($target === null) {
                throw new RuntimeException('Volume is missing or has an invalid transform filesystem handle.');
            }

            return Filesystems::disk($target, $values->subpath);
        };
    }

    public function transformHasUrls(): Closure
    {
        return function(): bool {
            /**
             * @var Volume $this
             * @phpstan-ignore-next-line Macro closure is rebound to Volume.
             */
            return $this->getTransformFs()->getRootUrl() !== null;
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
