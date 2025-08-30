<?php

namespace CraftCms\Cms\Utility\Utilities;

use Composer\InstalledVersions;
use Craft;
use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Utility\Utility;
use Illuminate\Support\Facades\DB;
use OutOfBoundsException;
use RequirementsChecker;
use yii\base\Module;

use function CraftCms\Cms\normalizeVersion;

/**
 * SystemReport represents a SystemReport dashboard widget.
 *
 * @since 6.0.0
 */
final class SystemReport extends Utility
{
    /**
     * {@inheritdoc}
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'System Report');
    }

    /**
     * {@inheritdoc}
     */
    public static function id(): string
    {
        return 'system-report';
    }

    /**
     * {@inheritdoc}
     */
    public static function icon(): string
    {
        return 'list-check';
    }

    /**
     * {@inheritdoc}
     */
    public static function contentHtml(): string
    {
        $modules = collect(Craft::$app->getModules())
            ->map(function (mixed $module): string {
                if ($module instanceof PluginInterface) {
                    return '';
                }

                if ($module instanceof Module) {
                    return get_class($module);
                }

                if (is_string($module)) {
                    return $module;
                }

                if (is_array($module) && isset($module['class'])) {
                    return $module['class'];
                }

                return Craft::t('app', 'Unknown type');
            })
            ->filter();

        $aliases = [];
        foreach (Aliases::getAll() as $alias => $value) {
            if (is_array($value)) {
                foreach ($value as $a => $v) {
                    $aliases[$a] = $v;
                }
            } else {
                $aliases[$alias] = $value;
            }
        }
        ksort($aliases);

        return Craft::$app->getView()->renderTemplate('_components/utilities/SystemReport.twig', [
            'appInfo' => self::appInfo(),
            'plugins' => app(Plugins::class)->getAllPlugins(),
            'modules' => $modules,
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
            'Craft edition & version' => sprintf('Craft %s %s', Craft::$app->edition->name, Craft::$app->getVersion()),
        ];

        if (! class_exists(InstalledVersions::class, false)) {
            $path = Craft::$app->getPath()->getVendorPath().DIRECTORY_SEPARATOR.'composer'.DIRECTORY_SEPARATOR.'InstalledVersions.php';
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
        $label = DB::getDriverTitle();
        $version = normalizeVersion(DB::getServerVersion());

        return "$label $version";
    }

    /**
     * Returns the image driver name and version
     */
    private static function imageDriver(): string
    {
        $imagesService = Craft::$app->getImages();

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

        $dbConfig = Craft::$app->getConfig()->getDb();
        $checker->dsn = $dbConfig->dsn;
        $checker->dbDriver = DB::getDriverName();
        $checker->dbUser = $dbConfig->user;
        $checker->dbPassword = $dbConfig->password;
        $checker->checkCraft();

        return $checker->getResult()['requirements'];
    }
}
