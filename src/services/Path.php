<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\helpers\Io;
use yii\base\Component;

/**
 * The Path service provides APIs for getting server paths that are used by Craft.
 *
 * An instance of the Path service is globally accessible in Craft via [[Application::path `Craft::$app->getPath()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Path extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_appPath;

    /**
     * @var
     */
    private $_configPath;

    /**
     * @var
     */
    private $_pluginsPath;

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
     * Returns the path to the craft/app/ folder.
     *
     * @return string The path to the craft/app/ folder.
     */
    public function getAppPath()
    {
        if (!isset($this->_appPath)) {
            $this->_appPath = Craft::getAlias('@app');
        }

        return $this->_appPath;
    }

    /**
     * Retursn the path to the craft/config/ folder.
     *
     * @return string The path to the craft/config/ folder.
     */
    public function getConfigPath()
    {
        if (!isset($this->_configPath)) {
            $this->_configPath = Craft::getAlias('@config');
        }

        return $this->_configPath;
    }

    /**
     * Returns the path to the craft/plugins/ folder.
     *
     * @return string The path to the craft/plugins/ folder.
     */
    public function getPluginsPath()
    {
        if (!isset($this->_pluginsPath)) {
            $this->_pluginsPath = Craft::getAlias('@plugins');
        }

        return $this->_pluginsPath;
    }

    /**
     * Returns the path to the craft/storage/ folder.
     *
     * @return string The path to the craft/storage/ folder.
     */
    public function getStoragePath()
    {
        if (!isset($this->_storagePath)) {
            $this->_storagePath = Craft::getAlias('@storage');
        }

        return $this->_storagePath;
    }

    /**
     * Returns the path to the craft/storage/rebrand/ folder.
     *
     * @return string
     */
    public function getRebrandPath()
    {
        $path = $this->getStoragePath().'/rebrand';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/app/vendor/ folder.
     *
     * @return string The path to the craft/app/vendor/ folder.
     */
    public function getVendorPath()
    {
        if (!isset($this->_vendorPath)) {
            $this->_vendorPath = Craft::getAlias('@vendor');
        }

        return $this->_vendorPath;
    }

    /**
     * Returns the path to the craft/storage/runtime/ folder.
     *
     * @return string The path to the craft/storage/runtime/ folder.
     */
    public function getRuntimePath()
    {
        $path = $this->getStoragePath().'/runtime';
        Io::ensureFolderExists($path);

        if (!Io::fileExists($path.'/.gitignore')) {
            Io::writeToFile($path.'/.gitignore', "*\n!.gitignore\n\n", true);
        }

        return $path;
    }

    /**
     * Returns the path to the craft/storage/backups/ folder.
     *
     * @return string The path to the craft/storage/backups/ folder.
     */
    public function getDbBackupPath()
    {
        $path = $this->getStoragePath().'/backups';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/temp/ folder.
     *
     * @return string The path to the craft/storage/runtime/temp/ folder.
     */
    public function getTempPath()
    {
        $path = $this->getRuntimePath().'/temp';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/temp/uploads/ folder.
     *
     * @return string The path to the craft/storage/runtime/temp/uploads/ folder.
     */
    public function getTempUploadsPath()
    {
        $path = $this->getTempPath().'/uploads';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/assets/ folder.
     *
     * @return string The path to the craft/storage/runtime/assets/ folder.
     */
    public function getAssetsPath()
    {
        $path = $this->getRuntimePath().'/assets';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/cache/assets/ folder.
     *
     * @return string The path to the craft/storage/runtime/cache/assets/ folder.
     */
    public function getAssetsCachePath()
    {
        $path = $this->getAssetsPath().'/cache';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/assets/tempuploads/ folder.
     *
     * @return string The path to the craft/storage/runtime/assets/tempuploads/ folder.
     */
    public function getAssetsTempVolumePath()
    {
        $path = $this->getAssetsPath().'/tempuploads';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/assets/sources/ folder.
     *
     * @return string The path to the craft/storage/runtime/assets/sources/ folder.
     */
    public function getAssetsImageSourcePath()
    {
        $path = $this->getAssetsCachePath().'/sources';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/assets/resized/ folder.
     *
     * @return string The path to the craft/storage/runtime/assets/resized/ folder.
     */
    public function getResizedAssetsPath()
    {
        $path = $this->getAssetsCachePath().'/resized';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/assets/icons/ folder.
     *
     * @return string The path to the craft/storage/runtime/assets/icons/ folder.
     */
    public function getAssetsIconsPath()
    {
        $path = $this->getAssetsCachePath().'/icons';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/pluginicons/ folder.
     *
     * @return string The path to the craft/storage/runtime/pluginicons/ folder.
     */
    public function getPluginIconsPath()
    {
        $path = $this->getRuntimePath().'/pluginicons';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/logs/ folder.
     *
     * @return string The path to the craft/storage/logs/ folder.
     */
    public function getLogPath()
    {
        $path = $this->getStoragePath().'/logs';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/app/resources/ folder.
     *
     * @return string The path to the craft/app/resources/ folder.
     */
    public function getResourcesPath()
    {
        return $this->getAppPath().'/resources';
    }

    /**
     * Returns the path to the craft/app/migrations/ folder, or the path to a plugin’s migrations/ folder.
     *
     * @param string $pluginHandle The plugin handle whose migrations/ folder should be returned. Defaults to `null`.
     *
     * @return string The path to the migrations/ folder.
     */
    public function getMigrationsPath($pluginHandle = null)
    {
        if ($pluginHandle) {
            return $this->getPluginsPath()."/$pluginHandle/migrations";
        }

        return $this->getAppPath().'/migrations';
    }

    /**
     * Returns the path to the craft/app/translations/ folder.
     *
     * @return string The path to the craft/app/translations/ folder.
     */
    public function getCpTranslationsPath()
    {
        return $this->getAppPath().'/translations';
    }

    /**
     * Returns the path to the craft/translations/ folder.
     *
     * @return string The path to the craft/translations/ folder.
     */
    public function getSiteTranslationsPath()
    {
        if (!isset($this->_siteTranslationsPath)) {
            $this->_siteTranslationsPath = Craft::getAlias('@translations');
        }

        return $this->_siteTranslationsPath;
    }

    /**
     * Returns the path to the craft/app/templates/ folder.
     *
     * @return string The path to the craft/app/templates/ folder.
     */
    public function getCpTemplatesPath()
    {
        return $this->getAppPath().'/templates';
    }

    /**
     * Returns the path to the craft/templates/ folder.
     *
     * @return string The path to the craft/templates/ folder.
     */
    public function getSiteTemplatesPath()
    {
        return Craft::getAlias('@templates');
    }

    /**
     * Returns the path to the craft/storage/runtime/compiled_templates/ folder.
     *
     * @return string The path to the craft/storage/runtime/compiled_templates/ folder.
     */
    public function getCompiledTemplatesPath()
    {
        $path = $this->getRuntimePath().'/compiled_templates';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the craft/storage/runtime/sessions/ folder.
     *
     * @return string The path to the craft/storage/runtime/sessions/ folder.
     */
    public function getSessionPath()
    {
        $path = $this->getRuntimePath().'/sessions';
        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to the file cache folder.
     *
     * This will be located at craft/storage/runtime/cache/ by default, but that can be overridden with the 'cachePath'
     * config setting in craft/config/filecache.php.
     *
     * @return string The path to the file cache folder.
     */
    public function getCachePath()
    {
        $path = Craft::getAlias(Craft::$app->getConfig()->get('cachePath', Config::CATEGORY_FILECACHE));

        if (!$path) {
            $path = $this->getRuntimePath().'/cache';
        }

        Io::ensureFolderExists($path);

        return $path;
    }

    /**
     * Returns the path to craft/config/license.key.
     *
     * @return string The path to craft/config/license.key.
     */
    public function getLicenseKeyPath()
    {
        return $this->getConfigPath().'/license.key';
    }
}
