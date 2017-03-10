<?php
namespace Craft;

/**
 * PathService provides APIs for getting server paths that are used by Craft.
 *
 * An instance of PathService is globally accessible in Craft via {@link WebApp::path `craft()->path`}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class PathService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the path to the craft/app/ folder.
	 *
	 * @return string The path to the craft/app/ folder.
	 */
	public function getAppPath()
	{
		return CRAFT_APP_PATH;
	}

	/**
	 * Retursn the path to the craft/config/ folder.
	 *
	 * @return string The path to the craft/config/ folder.
	 */
	public function getConfigPath()
	{
		return CRAFT_CONFIG_PATH;
	}

	/**
	 * Returns the path to the craft/plugins/ folder.
	 *
	 * @return string The path to the craft/plugins/ folder.
	 */
	public function getPluginsPath()
	{
		return CRAFT_PLUGINS_PATH;
	}

	/**
	 * Returns the path to the craft/storage/ folder.
	 *
	 * @return string The path to the craft/storage/ folder.
	 */
	public function getStoragePath()
	{
		return CRAFT_STORAGE_PATH;
	}

	/**
	 * Returns the path to the craft/storage/rebrand/ folder.
	 *
	 * @return string
	 */
	public function getRebrandPath()
	{
		$path = $this->getStoragePath().'rebrand/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/ folder.
	 */
	public function getRuntimePath()
	{
		$path = $this->getStoragePath().'runtime/';
		IOHelper::ensureFolderExists($path);

		if (!IOHelper::fileExists($path.'.gitignore'))
		{
			IOHelper::writeToFile($path.'.gitignore', "*\n!.gitignore\n\n", true);
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
		$path = $this->getStoragePath().'backups/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/temp/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/temp/ folder.
	 */
	public function getTempPath()
	{
		$path = $this->getRuntimePath().'temp/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/temp/uploads/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/temp/uploads/ folder.
	 */
	public function getTempUploadsPath()
	{
		$path = $this->getTempPath().'uploads/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/userphotos/ folder.
	 *
	 * @return string The path to the craft/storage/userphotos/ folder.
	 */
	public function getUserPhotosPath()
	{
		$path = $this->getStoragePath().'userphotos/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/assets/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/assets/ folder.
	 */
	public function getAssetsPath()
	{
		$path = $this->getRuntimePath().'assets/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/assets/tempuploads/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/assets/tempuploads/ folder.
	 */
	public function getAssetsTempSourcePath()
	{
		$path = $this->getAssetsPath().'tempuploads/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/assets/sources/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/assets/sources/ folder.
	 */
	public function getAssetsImageSourcePath()
	{
		$path = $this->getAssetsPath().'sources/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/assets/thumbs/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/assets/thumbs/ folder.
	 */
	public function getAssetsThumbsPath()
	{
		$path = $this->getAssetsPath().'thumbs/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/assets/icons/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/assets/icons/ folder.
	 */
	public function getAssetsIconsPath()
	{
		$path = $this->getAssetsPath().'icons/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/pluginicons/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/pluginicons/ folder.
	 */
	public function getPluginIconsPath()
	{
		$path = $this->getRuntimePath().'pluginicons/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/logs/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/logs/ folder.
	 */
	public function getLogPath()
	{
		$path = $this->getRuntimePath().'logs/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/state/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/state/ folder.
	 */
	public function getStatePath()
	{
		$path = $this->getRuntimePath().'state/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/app/lib/ folder.
	 *
	 * @return string The path to the craft/app/lib/ folder.
	 */
	public function getLibPath()
	{
		return $this->getAppPath().'lib/';
	}

	/**
	 * Returns the path to the craft/app/resources/ folder.
	 *
	 * @return string The path to the craft/app/resources/ folder.
	 */
	public function getResourcesPath()
	{
		return $this->getAppPath().'resources/';
	}

	/**
	 * Returns the path to the framework/ folder.
	 *
	 * @return string The path to the framework/ folder.
	 */
	public function getFrameworkPath()
	{
		return CRAFT_FRAMEWORK_PATH;
	}

	/**
	 * Returns the path to the vendor/ folder.
	 *
	 * @return string The path to the vendor/ folder.
	 */
	public function getVendorPath()
	{
		return CRAFT_VENDOR_PATH;
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
		if ($pluginHandle)
		{
			return $this->getPluginsPath().StringHelper::toLowerCase($pluginHandle).'/migrations/';
		}

		return $this->getAppPath().'migrations/';
	}

	/**
	 * Returns the path to the craft/app/translations/ folder.
	 *
	 * @return string The path to the craft/app/translations/ folder.
	 */
	public function getCpTranslationsPath()
	{
		return $this->getAppPath().'translations/';
	}

	/**
	 * Returns the path to the craft/translations/ folder.
	 *
	 * @return string The path to the craft/translations/ folder.
	 */
	public function getSiteTranslationsPath()
	{
		return CRAFT_TRANSLATIONS_PATH;
	}

	/**
	 * Returns the current templates path, taking into account whether this is a
	 * CP or Site request.
	 *
	 * @return string The templates path.
	 *
	 * @deprecated Deprecated in 2.6.2778. Use TemplatesService::getTemplatesPath() or TemplatesService::getTemplateMode() instead.
	 */
	public function getTemplatesPath()
	{
		return craft()->templates->getTemplatesPath();
	}

	/**
	 * Sets the current templates path.
	 *
	 * @param string $path The new templates path.
	 *
	 * @deprecated Deprecated in 2.6.2778. Use TemplatesService::setTemplatesPath() or TemplatesService::setTemplateMode() instead.
	 */
	public function setTemplatesPath($path)
	{
		craft()->templates->setTemplatesPath($path);
	}

	/**
	 * Returns the path to the craft/app/templates/ folder.
	 *
	 * @return string The path to the craft/app/templates/ folder.
	 */
	public function getCpTemplatesPath()
	{
		return $this->getAppPath().'templates/';
	}

	/**
	 * Returns the path to the craft/templates/ folder.
	 *
	 * @return string The path to the craft/templates/ folder.
	 */
	public function getSiteTemplatesPath()
	{
		return CRAFT_TEMPLATES_PATH;
	}

	/**
	 * Returns the path to the craft/storage/runtime/compiled_templates/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/compiled_templates/ folder.
	 */
	public function getCompiledTemplatesPath()
	{
		$path = $this->getRuntimePath().'compiled_templates/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to the craft/storage/runtime/sessions/ folder.
	 *
	 * @return string The path to the craft/storage/runtime/sessions/ folder.
	 */
	public function getSessionPath()
	{
		$path = $this->getRuntimePath().'sessions/';
		IOHelper::ensureFolderExists($path);
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
		$path = craft()->config->get('cachePath', ConfigFile::FileCache);

		if (!$path)
		{
			$path = $this->getRuntimePath().'cache/';
		}

		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * Returns the path to craft/config/license.key.
	 *
	 * @return string The path to craft/config/license.key.
	 */
	public function getLicenseKeyPath()
	{
		return $this->getConfigPath().'license.key';
	}
}
