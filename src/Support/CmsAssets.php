<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use Composer\InstalledVersions;
use RuntimeException;

use function Illuminate\Filesystem\join_paths;

class CmsAssets
{
    private const string PACKAGE = 'craftcms/cms-assets';

    private static ?string $packagePath = null;

    public static function packagePath(string $path = ''): string
    {
        self::$packagePath ??= InstalledVersions::getInstallPath(self::PACKAGE);

        if (self::$packagePath === null) {
            throw new RuntimeException(sprintf('Unable to locate the %s package.', self::PACKAGE));
        }

        return self::path(self::$packagePath, $path);
    }

    public static function resourcesPath(string $path = ''): string
    {
        return self::packagePath(self::path('resources', $path));
    }

    private static function path(string $basePath, string $path = ''): string
    {
        return $path === '' ? $basePath : File::normalizePath(join_paths($basePath, $path));
    }
}
