<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\FileHelper;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Path service provides APIs for getting server paths that are used by Craft.
 * An instance of the Path service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getPath()|`Craft::$app->path`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Path extends Component
{
    /**
     * @var
     */
    private $_configPath;

    /**
     * @var
     */
    private $_storagePath;

    /**
     * @var
     */
    private $_testsPath;

    /**
     * @var
     */
    private $_siteTranslationsPath;

    /**
     * @var
     */
    private $_vendorPath;

    /**
     * Returns the path to the `config/` directory.
     *
     * @return string
     * @throws Exception
     */
    public function getConfigPath(): string
    {
        if ($this->_configPath !== null) {
            return $this->_configPath;
        }

        $configPath = Craft::getAlias('@config');

        if ($configPath === false) {
            throw new Exception('There was a problem getting the config path.');
        }

        return $this->_configPath = FileHelper::normalizePath($configPath);
    }

    /**
     * Returns the path to `config/project/project.yaml`.
     *
     * @return string
     * @since 3.1.2
     */
    public function getProjectConfigFilePath(): string
    {
        return $this->getProjectConfigPath(false) . DIRECTORY_SEPARATOR . ProjectConfig::CONFIG_FILENAME;
    }

    /**
     * Returns the path to `config/project/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     * @since 3.5.0
     */
    public function getProjectConfigPath(bool $create = true): string
    {
        $path = $this->getConfigPath() . DIRECTORY_SEPARATOR . Craft::$app->getProjectConfig()->folderName;

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     */
    public function getStoragePath(bool $create = true): string
    {
        if ($this->_storagePath === null) {
            $path = Craft::getAlias('@storage');

            if ($path === false) {
                throw new Exception('There was a problem getting the storage path.');
            }

            $this->_storagePath = FileHelper::normalizePath($path);
        }

        if ($create) {
            FileHelper::createDirectory($this->_storagePath);
        }

        return $this->_storagePath;
    }

    /**
     * Returns the path to the `tests/` directory.
     *
     * @return string
     * @throws Exception
     * @since 3.4.29
     */
    public function getTestsPath(): string
    {
        if ($this->_testsPath !== null) {
            return $this->_testsPath;
        }

        $path = Craft::getAlias('@tests');

        if ($path === false) {
            throw new Exception('There was a problem getting the tests path.');
        }

        return $this->_testsPath = FileHelper::normalizePath($path);
    }

    /**
     * Returns the path to the `storage/composer-backups/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     * @since 3.0.38
     */
    public function getComposerBackupsPath(bool $create = true): string
    {
        $path = $this->getStoragePath($create) . DIRECTORY_SEPARATOR . 'composer-backups';

        if ($create) {
            FileHelper::createDirectory($path);
            FileHelper::writeGitignoreFile($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/config-backups/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     * @since 3.1.0
     */
    public function getConfigBackupPath(bool $create = true): string
    {
        $path = $this->getStoragePath($create) . DIRECTORY_SEPARATOR . 'config-backups';

        if ($create) {
            FileHelper::createDirectory($path);
            FileHelper::writeGitignoreFile($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/config-deltas/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     * @since 3.4.0
     */
    public function getConfigDeltaPath(bool $create = true): string
    {
        $path = $this->getStoragePath($create) . DIRECTORY_SEPARATOR . 'config-deltas';

        if ($create) {
            FileHelper::createDirectory($path);
            FileHelper::writeGitignoreFile($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/rebrand/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     */
    public function getRebrandPath(bool $create = true): string
    {
        $path = $this->getStoragePath($create) . DIRECTORY_SEPARATOR . 'rebrand';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `vendor/` directory.
     *
     * @return string
     * @throws Exception
     */
    public function getVendorPath(): string
    {
        if ($this->_vendorPath !== null) {
            return $this->_vendorPath;
        }

        $vendorPath = Craft::getAlias('@vendor');

        if ($vendorPath === false) {
            throw new Exception('There was a problem getting the vendor path.');
        }

        return $this->_vendorPath = FileHelper::normalizePath($vendorPath);
    }

    /**
     * Returns the path to the `storage/runtime/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     */
    public function getRuntimePath(bool $create = true): string
    {
        $path = $this->getStoragePath($create) . DIRECTORY_SEPARATOR . 'runtime';

        if ($create) {
            FileHelper::createDirectory($path);
            FileHelper::writeGitignoreFile($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/backups/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     */
    public function getDbBackupPath(bool $create = true): string
    {
        $path = $this->getStoragePath($create) . DIRECTORY_SEPARATOR . 'backups';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/temp/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getTempPath(bool $create = true): string
    {
        $path = $this->getRuntimePath($create) . DIRECTORY_SEPARATOR . 'temp';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getAssetsPath(bool $create = true): string
    {
        $path = $this->getRuntimePath($create) . DIRECTORY_SEPARATOR . 'assets';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/tempuploads/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getTempAssetUploadsPath(bool $create = true): string
    {
        $path = $this->getAssetsPath($create) . DIRECTORY_SEPARATOR . 'tempuploads';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/sources/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getAssetSourcesPath(bool $create = true): string
    {
        $path = $this->getAssetsPath($create) . DIRECTORY_SEPARATOR . 'sources';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/imageeditor/` folder.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getImageEditorSourcesPath(bool $create = true): string
    {
        $path = $this->getAssetsPath($create) . DIRECTORY_SEPARATOR . 'imageeditor';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/thumbs/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getAssetThumbsPath(bool $create = true): string
    {
        $path = $this->getAssetsPath($create) . DIRECTORY_SEPARATOR . 'thumbs';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/icons/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getAssetsIconsPath(bool $create = true): string
    {
        $path = $this->getAssetsPath($create) . DIRECTORY_SEPARATOR . 'icons';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/pluginicons/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getPluginIconsPath(bool $create = true): string
    {
        $path = $this->getRuntimePath($create) . DIRECTORY_SEPARATOR . 'pluginicons';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/logs/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     * @throws Exception
     */
    public function getLogPath(bool $create = true): string
    {
        $path = $this->getStoragePath($create) . DIRECTORY_SEPARATOR . 'logs';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `app/translations/` directory.
     *
     * @return string
     */
    public function getCpTranslationsPath(): string
    {
        return Craft::$app->getBasePath() . DIRECTORY_SEPARATOR . 'translations';
    }

    /**
     * Returns the path to the `translations/` directory.
     *
     * @return string
     * @throws Exception
     */
    public function getSiteTranslationsPath(): string
    {
        if ($this->_siteTranslationsPath !== null) {
            return $this->_siteTranslationsPath;
        }

        $translationsPath = Craft::getAlias('@translations');

        if ($translationsPath === false) {
            throw new Exception('There was a problem getting the translations path.');
        }

        return $this->_siteTranslationsPath = $translationsPath;
    }

    /**
     * Returns the path to the `app/templates/` directory.
     *
     * @return string
     */
    public function getCpTemplatesPath(): string
    {
        return Craft::$app->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
    }

    /**
     * Returns the path to the `templates/` directory.
     *
     * @return string
     * @throws Exception
     */
    public function getSiteTemplatesPath(): string
    {
        $siteTemplatesPath = Craft::getAlias('@templates');

        if ($siteTemplatesPath === false) {
            throw new Exception('There was a problem getting the site templates path.');
        }

        return FileHelper::normalizePath($siteTemplatesPath);
    }

    /**
     * Returns the path to the `storage/runtime/compiled_classes/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getCompiledClassesPath(bool $create = true): string
    {
        $path = $this->getRuntimePath($create) . DIRECTORY_SEPARATOR . 'compiled_classes';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/compiled_templates/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getCompiledTemplatesPath(bool $create = true): string
    {
        $path = $this->getRuntimePath($create) . DIRECTORY_SEPARATOR . 'compiled_templates';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/sessions/` directory.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getSessionPath(bool $create = true): string
    {
        $path = $this->getRuntimePath($create) . DIRECTORY_SEPARATOR . 'sessions';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the file cache directory.
     *
     * This will be located at `storage/runtime/cache/` by default, but that can be overridden with the 'cachePath'
     * config setting in `config/filecache.php`.
     *
     * @param bool $create Whether the directory should be created if it doesn't exist
     * @return string
     */
    public function getCachePath(bool $create = true): string
    {
        $path = $this->getRuntimePath($create) . DIRECTORY_SEPARATOR . 'cache';

        if ($create) {
            FileHelper::createDirectory($path);
        }

        return $path;
    }

    /**
     * Returns the path to the license key file.
     *
     * @return string
     */
    public function getLicenseKeyPath(): string
    {
        return defined('CRAFT_LICENSE_KEY_PATH') ? CRAFT_LICENSE_KEY_PATH : $this->getConfigPath() . DIRECTORY_SEPARATOR . 'license.key';
    }
}
