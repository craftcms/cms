<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Filesystem;

use craft\base\BaseFsInterface;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Filesystems;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use InvalidArgumentException;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\PathPrefixing\PathPrefixedAdapter;
use Throwable;

readonly class FilesystemCompatibility
{
    public function register(Application $app): void
    {
        $app->make(FilesystemManager::class)->extend(LegacyFsFlysystemAdapter::DISK_DRIVER, function($app, array $config) {
            $handle = $config['fsHandle'] ?? null;
            if (!is_string($handle) || $handle === '') {
                throw new InvalidArgumentException('Missing `fsHandle` configuration for craft-fs-bridge disk.');
            }

            $filesystem = Filesystems::getFilesystemByHandle($handle);
            if (!$filesystem instanceof FsInterface) {
                throw new InvalidArgumentException("Craft filesystem [$handle] is not registered.");
            }

            try {
                $diskConfig = $filesystem->getDiskConfig();
                if (
                    ($diskConfig['driver'] ?? null) === LegacyFsFlysystemAdapter::DISK_DRIVER &&
                    ($diskConfig['fsHandle'] ?? null) === $handle
                ) {
                    if (!$filesystem instanceof BaseFsInterface) {
                        throw new InvalidArgumentException(
                            "Filesystem [$handle] does not provide a usable Laravel disk configuration.",
                        );
                    }

                    return self::legacyFilesystemAdapter($filesystem, array_merge($config, $diskConfig));
                }

                $disk = $app->make(FilesystemManager::class)->build($diskConfig);

                if (!$disk instanceof LaravelFilesystemAdapter) {
                    throw new InvalidArgumentException("Filesystem [$handle] returned an invalid disk configuration.");
                }

                return self::filesystemWithPrefix($disk, $config);
            } catch (Throwable $e) {
                if (!$filesystem instanceof BaseFsInterface) {
                    throw new InvalidArgumentException(
                        "Filesystem [$handle] does not provide a usable Laravel disk configuration.",
                        previous: $e,
                    );
                }

                Deprecator::log(
                    sprintf('filesystem-bridge-fallback:%s', $filesystem::class),
                    sprintf(
                        'Filesystem [%s] is using a legacy operation fallback. Implement `%s::getDiskConfig()` so it can be used as a native Laravel disk.',
                        $handle,
                        $filesystem::class,
                    ),
                );

                return self::legacyFilesystemAdapter($filesystem, $config);
            }
        });
    }

    private static function filesystemWithPrefix(LaravelFilesystemAdapter $disk, array $config): LaravelFilesystemAdapter
    {
        $prefix = $config['prefix'] ?? null;
        if (!is_string($prefix) || $prefix === '') {
            return $disk;
        }

        $flysystemAdapter = new PathPrefixedAdapter($disk->getAdapter(), $prefix);

        return new LaravelFilesystemAdapter(
            new Flysystem($flysystemAdapter, Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ])),
            $flysystemAdapter,
            array_merge($disk->getConfig(), $config),
        );
    }

    private static function legacyFilesystemAdapter(BaseFsInterface $filesystem, array $config): LaravelFilesystemAdapter
    {
        $adapter = new LegacyFsFlysystemAdapter($filesystem);
        $flysystemAdapter = !empty($config['prefix'])
            ? new PathPrefixedAdapter($adapter, $config['prefix'])
            : $adapter;

        return new LaravelFilesystemAdapter(
            new Flysystem($flysystemAdapter, Arr::only($config, [
                'directory_visibility',
                'disable_asserts',
                'retain_visibility',
                'temporary_url',
                'url',
                'visibility',
            ])),
            $flysystemAdapter,
            $config,
        );
    }
}
