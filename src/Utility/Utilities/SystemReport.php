<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Composer\InstalledVersions;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Image\Images;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use OutOfBoundsException;
use Override;
use RequirementsChecker;

use function CraftCms\Cms\normalizeVersion;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * SystemReport represents a SystemReport dashboard widget.
 */
class SystemReport extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('System Report');
    }

    #[Override]
    public static function id(): string
    {
        return 'system-report';
    }

    #[Override]
    public static function icon(): string
    {
        return 'list-check';
    }

    #[Override]
    public static function contentHtml(): string
    {
        $aliases = [];
        foreach (Aliases::getAll() as $alias => $value) {
            if (is_array($value)) {
                foreach ($value as $a => $v) {
                    if (! str_starts_with((string) $a, '@appicons/')) {
                        $aliases[$a] = $v;
                    }
                }
            } elseif (! str_starts_with((string) $alias, '@appicons/')) {
                $aliases[$alias] = $value;
            }
        }
        ksort($aliases);

        return template('_components/utilities/SystemReport', [
            'appInfo' => self::appInfo(),
            'plugins' => app(Plugins::class)->getAllPlugins(),
            'aliases' => $aliases,
            'requirements' => self::requirementResults(),
        ]);
    }

    /**
     * Returns application info.
     */
    private static function appInfo(): array
    {
        $info = [
            'PHP version' => PHP::version(),
            'OS version' => PHP_OS.' '.php_uname('r'),
            'Database driver & version' => self::dbDriver(),
            'Image driver & version' => self::imageDriver(),
            'Craft edition & version' => sprintf('Craft %s %s', Edition::get()->name, Cms::VERSION),
        ];

        if (! class_exists(InstalledVersions::class, false)) {
            $path = Path::vendor('composer/InstalledVersions.php');
            if (file_exists($path)) {
                require $path;
            }
        }

        if (class_exists(InstalledVersions::class, false)) {
            $info = self::addVersion($info, 'Laravel version', 'laravel/framework');
            $info = self::addVersion($info, 'Yii version', 'yiisoft/yii2');
            $info = self::addVersion($info, 'Twig version', 'twig/twig');
            $info = self::addVersion($info, 'Guzzle version', 'guzzlehttp/guzzle');
        }

        return $info;
    }

    private static function addVersion(array $info, string $label, string $packageName): array
    {
        try {
            $version = InstalledVersions::getPrettyVersion($packageName) ?? InstalledVersions::getVersion($packageName);
        } catch (OutOfBoundsException) {
            return $info;
        }

        if ($version !== null) {
            $info[$label] = $version;
        }

        return $info;
    }

    /**
     * Returns the DB driver name and version
     */
    private static function dbDriver(): string
    {
        $label = DB::driverLabel();
        $version = normalizeVersion(DB::getServerVersion());

        return "$label $version";
    }

    /**
     * Returns the image driver name and version
     */
    private static function imageDriver(): string
    {
        $imagesService = app(Images::class);

        $driverName = $imagesService->getIsGd()
            ? 'GD'
            : 'Imagick';

        return $driverName.' '.$imagesService->getVersion();
    }

    /**
     * Runs the requirements checker and returns its results.
     */
    private static function requirementResults(): array
    {
        $checker = new RequirementsChecker;

        $prefix = 'database.connections.'.DB::getDriverName();
        $checker->dsn = implode('', [
            DB::getDriverName(),
            ':host='.Config::get("$prefix.host"),
            ';port='.Config::get("$prefix.port"),
            ';dbname='.Config::get("$prefix.database"),
            ';user='.Config::get("$prefix.username"),
            ';password='.Config::get("$prefix.password"),
        ]);
        $checker->dbDriver = DB::getDriverName();
        $checker->dbUser = Config::get("$prefix.username");
        $checker->dbPassword = Config::get("$prefix.password");
        $checker->checkCraft();

        return $checker->getResult()['requirements'];
    }
}
