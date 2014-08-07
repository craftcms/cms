<?php
namespace Craft;

/**
 * Class PathService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class PathService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_templatesPath;

	// Public Methods
	// =========================================================================

	/**
	 * @return string
	 */
	public function getAppPath()
	{
		return CRAFT_APP_PATH;
	}

	/**
	 * @return string
	 */
	public function getConfigPath()
	{
		return CRAFT_CONFIG_PATH;
	}

	/**
	 * @return string
	 */
	public function getPluginsPath()
	{
		return CRAFT_PLUGINS_PATH;
	}

	/**
	 * @return string
	 */
	public function getStoragePath()
	{
		return CRAFT_STORAGE_PATH;
	}

	/**
	 * @return string
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
	 * @return string
	 */
	public function getDbBackupPath()
	{
		$path = $this->getStoragePath().'backups/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getTempPath()
	{
		$path = $this->getRuntimePath().'temp/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getTempUploadsPath()
	{
		$path = $this->getTempPath().'uploads/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getUserPhotosPath()
	{
		$path = $this->getStoragePath().'userphotos/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getAssetsPath()
	{
		$path = $this->getRuntimePath().'assets/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getAssetsTempSourcePath()
	{
		$path = $this->getAssetsPath().'tempuploads/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getAssetsImageSourcePath()
	{
		$path = $this->getAssetsPath().'sources/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getAssetsThumbsPath()
	{
		$path = $this->getAssetsPath().'thumbs/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getAssetsIconsPath()
	{
		$path = $this->getAssetsPath().'icons/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getLogPath()
	{
		$path = $this->getRuntimePath().'logs/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getStatePath()
	{
		$path = $this->getRuntimePath().'state/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getLibPath()
	{
		return $this->getAppPath().'lib/';
	}

	/**
	 * @return string
	 */
	public function getResourcesPath()
	{
		return $this->getAppPath().'resources/';
	}

	/**
	 * @return mixed
	 */
	public function getFrameworkPath()
	{
		return $this->getAppPath().'framework/';
	}

	/**
	 * @param string|null $pluginHandle
	 *
	 * @return string
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
	 * @return string
	 */
	public function getCpTranslationsPath()
	{
		return $this->getAppPath().'translations/';
	}

	/**
	 * @return string
	 */
	public function getSiteTranslationsPath()
	{
		return CRAFT_TRANSLATIONS_PATH;
	}

	/**
	 * Returns the current templates path, taking into account whether this is a CP or Site request.
	 *
	 * @return string
	 */
	public function getTemplatesPath()
	{
		if (!isset($this->_templatesPath))
		{
			if (craft()->request->isCpRequest())
			{
				$this->_templatesPath = $this->getCpTemplatesPath();
			}
			else
			{
				$this->_templatesPath = $this->getSiteTemplatesPath();
			}
		}

		return $this->_templatesPath;
	}

	/**
	 * Sets the current templates path.
	 *
	 * @param string $path
	 */
	public function setTemplatesPath($path)
	{
		$this->_templatesPath = $path;
	}

	/**
	 * Returns the Craft CP templates path.
	 *
	 * @return string
	 */
	public function getCpTemplatesPath()
	{
		return $this->getAppPath().'templates/';
	}

	/**
	 * Returns the site templates path.
	 *
	 * @return string
	 */
	public function getSiteTemplatesPath()
	{
		return CRAFT_TEMPLATES_PATH;
	}

	/**
	 * Returns the path to the offline template by first checking to see if they have set a custom path in config.
	 * If that is not set, it will fall back on the default CP offline template.
	 *
	 * @return mixed
	 */
	public function getOfflineTemplatePath()
	{
		// If the user has set offlinePath config item, let's use it.
		if (($path = craft()->config->get('offlinePath')) !== null)
		{
			return mb_substr($path, 0, mb_strlen($path) - mb_strlen(IOHelper::getFileName($path)));
		}

		return $this->getCpTemplatesPath();
	}

	/**
	 * Returns the current parsed templates path, taking into account whether this is a CP or Site request.
	 *
	 * @return mixed
	 */
	public function getCompiledTemplatesPath()
	{
		$path = $this->getRuntimePath().'compiled_templates/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getSessionPath()
	{
		$path = $this->getRuntimePath().'sessions/';
		IOHelper::ensureFolderExists($path);
		return $path;
	}

	/**
	 * @return string
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
	 * Returns the path to the license key file.
	 *
	 * @return string
	 */
	public function getLicenseKeyPath()
	{
		return $this->getConfigPath().'license.key';
	}
}
