<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Path as LaravelPath;
use yii\base\Component;

/**
 * The Path service provides APIs for getting server paths that are used by Craft.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getPath()|`Craft::$app->getPath()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path} instead.
 */
class Path extends Component
{
    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::config()} instead.
     */
    public function getConfigPath(): string
    {
        return $this->service()->config();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::projectConfigFile()} instead.
     */
    public function getProjectConfigFilePath(): string
    {
        return $this->service()->projectConfigFile();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::projectConfig()} instead.
     */
    public function getProjectConfigPath(bool $create = true): string
    {
        return $this->service()->projectConfig(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::storage()} instead.
     */
    public function getStoragePath(bool $create = true): string
    {
        return $this->service()->storage(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::tests()} instead.
     */
    public function getTestsPath(): string
    {
        return $this->service()->tests();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::composerBackups()} instead.
     */
    public function getComposerBackupsPath(bool $create = true): string
    {
        return $this->service()->composerBackups(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::configBackup()} instead.
     */
    public function getConfigBackupPath(bool $create = true): string
    {
        return $this->service()->configBackup(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::configDelta()} instead.
     */
    public function getConfigDeltaPath(bool $create = true): string
    {
        return $this->service()->configDelta(create: $create);
    }

    /**
     * @deprecated 6.0.0
     */
    public function getRebrandPath(bool $create = true): string
    {
        $path = Env::get('CRAFT_REBRAND_PATH')
            ? Env::parse('$CRAFT_REBRAND_PATH')
            : $this->getStoragePath($create) . DIRECTORY_SEPARATOR . 'rebrand';

        if ($create) {
            File::ensureDirectoryExists($path);
        }

        return $path;
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::vendor()} instead.
     */
    public function getVendorPath(): string
    {
        return $this->service()->vendor();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::runtime()} instead.
     */
    public function getRuntimePath(bool $create = true): string
    {
        return $this->service()->runtime(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::dbBackup()} instead.
     */
    public function getDbBackupPath(bool $create = true): string
    {
        return $this->service()->dbBackup(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::temp()} instead.
     */
    public function getTempPath(bool $create = true): string
    {
        return $this->service()->temp(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::assets()} instead.
     */
    public function getAssetsPath(bool $create = true): string
    {
        return $this->service()->assets(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::tempAssetUploads()} instead.
     */
    public function getTempAssetUploadsPath(bool $create = true): string
    {
        return $this->service()->tempAssetUploads(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::assetSources()} instead.
     */
    public function getAssetSourcesPath(bool $create = true): string
    {
        return $this->service()->assetSources(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::imageEditorSources()} instead.
     */
    public function getImageEditorSourcesPath(bool $create = true): string
    {
        return $this->service()->imageEditorSources(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::assetsIcons()} instead.
     */
    public function getAssetsIconsPath(bool $create = true): string
    {
        return $this->service()->assetsIcons(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::imageTransforms()} instead.
     */
    public function getImageTransformsPath(bool $create = true): string
    {
        return $this->service()->imageTransforms(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::pluginIcons()} instead.
     */
    public function getPluginIconsPath(bool $create = true): string
    {
        return $this->service()->pluginIcons(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::logs()} instead.
     */
    public function getLogPath(bool $create = true): string
    {
        return $this->service()->logs(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::cpTranslations()} instead.
     */
    public function getCpTranslationsPath(): string
    {
        return $this->service()->cpTranslations();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::siteTranslations()} instead.
     */
    public function getSiteTranslationsPath(): string
    {
        return $this->service()->siteTranslations();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::cpTemplates()} instead.
     */
    public function getCpTemplatesPath(): string
    {
        return $this->service()->cpTemplates();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::siteTemplates()} instead.
     */
    public function getSiteTemplatesPath(): string
    {
        return $this->service()->siteTemplates();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::compiledClasses()} instead.
     */
    public function getCompiledClassesPath(bool $create = true): string
    {
        return $this->service()->compiledClasses(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::compiledTemplates()} instead.
     */
    public function getCompiledTemplatesPath(bool $create = true): string
    {
        return $this->service()->compiledTemplates(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::sessions()} instead.
     */
    public function getSessionPath(bool $create = true): string
    {
        return $this->service()->sessions(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::cache()} instead.
     */
    public function getCachePath(bool $create = true): string
    {
        return $this->service()->cache(create: $create);
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::licenseKey()} instead.
     */
    public function getLicenseKeyPath(): string
    {
        return $this->service()->licenseKey();
    }

    /**
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Path::system()} instead.
     */
    public function getSystemPaths(): array
    {
        return $this->service()->system();
    }

    private function service(): LaravelPath
    {
        return app(LaravelPath::class);
    }
}
