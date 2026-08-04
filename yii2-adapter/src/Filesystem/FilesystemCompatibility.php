<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Filesystem;

use craft\base\BaseFsInterface;
use craft\fs\bridge\LegacyFsFlysystemAdapter;
use craft\fs\bridge\LegacyFsPathPrefixedAdapter;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Filesystems;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use InvalidArgumentException;
use League\Flysystem\Filesystem as Flysystem;

readonly class FilesystemCompatibility
{
    public function register(Application $app): void
    {
        $legacyFilesystemAdapter = self::legacyFilesystemAdapter(...);

        $app->make(FilesystemManager::class)->extend(LegacyFsFlysystemAdapter::DISK_DRIVER, function($app, array $config) use ($legacyFilesystemAdapter) {
            $handle = $config['fsHandle'] ?? null;
            if (!is_string($handle) || $handle === '') {
                throw new InvalidArgumentException('Missing `fsHandle` configuration for craft-fs-bridge disk.');
            }

            $filesystem = Filesystems::getFilesystemByHandle($handle);
            if (!$filesystem instanceof BaseFsInterface) {
                throw new InvalidArgumentException("Craft filesystem [$handle] does not implement the legacy filesystem API.");
            }

            Deprecator::log(
                sprintf('filesystem-bridge:%s', $filesystem::class),
                sprintf(
                    'Filesystem [%s] uses the deprecated legacy filesystem API. Update `%s::getDiskConfig()` to return a native Laravel disk configuration.',
                    $handle,
                    $filesystem::class,
                ),
            );

            return $legacyFilesystemAdapter($filesystem, $config);
        });
    }

    private static function legacyFilesystemAdapter(BaseFsInterface $filesystem, array $config): LaravelFilesystemAdapter
    {
        $adapter = new LegacyFsFlysystemAdapter($filesystem);
        $flysystemAdapter = !empty($config['prefix'])
            ? new LegacyFsPathPrefixedAdapter($adapter, $config['prefix'])
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
            Arr::except($config, ['prefix']),
        );
    }
}
