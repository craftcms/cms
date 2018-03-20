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
 * An instance of the Path service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getPath()|<code>Craft::$app->path</code>]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Path extends Component
{
    // Properties
    // =========================================================================

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
    private $_siteTranslationsPath;

    /**
     * @var
     */
    private $_vendorPath;

    // Public Methods
    // =========================================================================

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
     * Returns the path to the `storage/` directory.
     *
     * @return string
     * @throws Exception
     */
    public function getStoragePath(): string
    {
        if ($this->_storagePath !== null) {
            return $this->_storagePath;
        }

        $storagePath = Craft::getAlias('@storage');

        if ($storagePath === false) {
            throw new Exception('There was a problem getting the storage path.');
        }

        return $this->_storagePath = FileHelper::normalizePath($storagePath);
    }

    /**
     * Returns the path to the `storage/rebrand/` directory.
     *
     * @return string
     */
    public function getRebrandPath(): string
    {
        $path = $this->getStoragePath().DIRECTORY_SEPARATOR.'rebrand';
        FileHelper::createDirectory($path);

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
     * @return string
     */
    public function getRuntimePath(): string
    {
        $path = $this->getStoragePath().DIRECTORY_SEPARATOR.'runtime';
        FileHelper::createDirectory($path);

        // Add a .gitignore file in there if there isn't one
        $gitignorePath = $path.DIRECTORY_SEPARATOR.'.gitignore';

        if (!is_file($gitignorePath)) {
            FileHelper::writeToFile($gitignorePath, "*\n!.gitignore\n", [
                // Prevent a segfault if this is called recursively
                'lock' => false,
            ]);
        }

        return $path;
    }

    /**
     * Returns the path to the `storage/backups/` directory.
     *
     * @return string
     */
    public function getDbBackupPath(): string
    {
        $path = $this->getStoragePath().DIRECTORY_SEPARATOR.'backups';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/temp/` directory.
     *
     * @return string
     */
    public function getTempPath(): string
    {
        $path = $this->getRuntimePath().DIRECTORY_SEPARATOR.'temp';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/` directory.
     *
     * @return string
     */
    public function getAssetsPath(): string
    {
        $path = $this->getRuntimePath().DIRECTORY_SEPARATOR.'assets';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/tempuploads/` directory.
     *
     * @return string
     */
    public function getTempAssetUploadsPath(): string
    {
        $path = $this->getAssetsPath().DIRECTORY_SEPARATOR.'tempuploads';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/sources/` directory.
     *
     * @return string
     */
    public function getAssetSourcesPath(): string
    {
        $path = $this->getAssetsPath().DIRECTORY_SEPARATOR.'sources';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/imageeditor/` folder.
     *
     * @return string
     */
    public function getImageEditorSourcesPath(): string
    {
        $path = $this->getAssetsPath().DIRECTORY_SEPARATOR.'imageeditor';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/thumbs/` directory.
     *
     * @return string
     */
    public function getAssetThumbsPath(): string
    {
        $path = $this->getAssetsPath().DIRECTORY_SEPARATOR.'thumbs';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/assets/icons/` directory.
     *
     * @return string
     */
    public function getAssetsIconsPath(): string
    {
        $path = $this->getAssetsPath().DIRECTORY_SEPARATOR.'icons';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/pluginicons/` directory.
     *
     * @return string
     */
    public function getPluginIconsPath(): string
    {
        $path = $this->getRuntimePath().DIRECTORY_SEPARATOR.'pluginicons';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/logs/` directory.
     *
     * @return string
     */
    public function getLogPath(): string
    {
        $path = $this->getStoragePath().DIRECTORY_SEPARATOR.'logs';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `app/translations/` directory.
     *
     * @return string
     */
    public function getCpTranslationsPath(): string
    {
        return Craft::$app->getBasePath().DIRECTORY_SEPARATOR.'translations';
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
        return Craft::$app->getBasePath().DIRECTORY_SEPARATOR.'templates';
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
     * @return string
     */
    public function getCompiledClassesPath(): string
    {
        $path = $this->getRuntimePath().DIRECTORY_SEPARATOR.'compiled_classes';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/compiled_templates/` directory.
     *
     * @return string
     */
    public function getCompiledTemplatesPath(): string
    {
        $path = $this->getRuntimePath().DIRECTORY_SEPARATOR.'compiled_templates';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the `storage/runtime/sessions/` directory.
     *
     * @return string
     */
    public function getSessionPath(): string
    {
        $path = $this->getRuntimePath().DIRECTORY_SEPARATOR.'sessions';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the file cache directory.
     * This will be located at `storage/runtime/cache/` by default, but that can be overridden with the 'cachePath'
     * config setting in `config/filecache.php`.
     *
     * @return string
     */
    public function getCachePath(): string
    {
        $path = $this->getRuntimePath().DIRECTORY_SEPARATOR.'cache';
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns the path to the license key file.
     *
     * @return string
     */
    public function getLicenseKeyPath(): string
    {
        return defined('CRAFT_LICENSE_KEY_PATH') ? CRAFT_LICENSE_KEY_PATH : $this->getConfigPath().DIRECTORY_SEPARATOR.'license.key';
    }
}
