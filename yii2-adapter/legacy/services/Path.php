<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CraftCms\Cms\Support\Path as LaravelPath;
use yii\base\Component;

/**
 * The Path service provides APIs for getting server paths that are used by Craft.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getPath()|`Craft::$app->getPath()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Path extends Component
{
    public function getConfigPath(): string
    {
        return $this->service()->config();
    }

    public function getProjectConfigFilePath(): string
    {
        return $this->service()->projectConfigFile();
    }

    public function getProjectConfigPath(bool $create = true): string
    {
        return $this->service()->projectConfig(create: $create);
    }

    public function getStoragePath(bool $create = true): string
    {
        return $this->service()->storage(create: $create);
    }

    public function getTestsPath(): string
    {
        return $this->service()->tests();
    }

    public function getComposerBackupsPath(bool $create = true): string
    {
        return $this->service()->composerBackups(create: $create);
    }

    public function getConfigBackupPath(bool $create = true): string
    {
        return $this->service()->configBackup(create: $create);
    }

    public function getConfigDeltaPath(bool $create = true): string
    {
        return $this->service()->configDelta(create: $create);
    }

    public function getRebrandPath(bool $create = true): string
    {
        return $this->service()->rebrand(create: $create);
    }

    public function getVendorPath(): string
    {
        return $this->service()->vendor();
    }

    public function getRuntimePath(bool $create = true): string
    {
        return $this->service()->runtime(create: $create);
    }

    public function getDbBackupPath(bool $create = true): string
    {
        return $this->service()->dbBackup(create: $create);
    }

    public function getTempPath(bool $create = true): string
    {
        return $this->service()->temp(create: $create);
    }

    public function getAssetsPath(bool $create = true): string
    {
        return $this->service()->assets(create: $create);
    }

    public function getTempAssetUploadsPath(bool $create = true): string
    {
        return $this->service()->tempAssetUploads(create: $create);
    }

    public function getAssetSourcesPath(bool $create = true): string
    {
        return $this->service()->assetSources(create: $create);
    }

    public function getImageEditorSourcesPath(bool $create = true): string
    {
        return $this->service()->imageEditorSources(create: $create);
    }

    public function getAssetsIconsPath(bool $create = true): string
    {
        return $this->service()->assetsIcons(create: $create);
    }

    public function getImageTransformsPath(bool $create = true): string
    {
        return $this->service()->imageTransforms(create: $create);
    }

    public function getPluginIconsPath(bool $create = true): string
    {
        return $this->service()->pluginIcons(create: $create);
    }

    public function getLogPath(bool $create = true): string
    {
        return $this->service()->logs(create: $create);
    }

    public function getCpTranslationsPath(): string
    {
        return $this->service()->cpTranslations();
    }

    public function getSiteTranslationsPath(): string
    {
        return $this->service()->siteTranslations();
    }

    public function getCpTemplatesPath(): string
    {
        return $this->service()->cpTemplates();
    }

    public function getSiteTemplatesPath(): string
    {
        return $this->service()->siteTemplates();
    }

    public function getCompiledClassesPath(bool $create = true): string
    {
        return $this->service()->compiledClasses(create: $create);
    }

    public function getCompiledTemplatesPath(bool $create = true): string
    {
        return $this->service()->compiledTemplates(create: $create);
    }

    public function getSessionPath(bool $create = true): string
    {
        return $this->service()->sessions(create: $create);
    }

    public function getCachePath(bool $create = true): string
    {
        return $this->service()->cache(create: $create);
    }

    public function getLicenseKeyPath(): string
    {
        return $this->service()->licenseKey();
    }

    public function getSystemPaths(): array
    {
        return $this->service()->system();
    }

    private function service(): LaravelPath
    {
        return app(LaravelPath::class);
    }
}
