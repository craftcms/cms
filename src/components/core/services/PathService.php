<?php
namespace Blocks;

/**
 *
 */
class PathService extends BaseApplicationComponent
{
	private $_templatePath;

	/**
	 * @return string
	 */
	public function getAppPath()
	{
		return BLOCKS_APP_PATH;
	}

	/**
	 * @return string
	 */
	public function getConfigPath()
	{
		return BLOCKS_CONFIG_PATH;
	}

	/**
	 * @return string
	 */
	public function getPluginsPath()
	{
		return BLOCKS_PLUGINS_PATH;
	}

	/**
	 * @return string
	 */
	public function getRuntimePath()
	{
		return BLOCKS_RUNTIME_PATH;
	}

	/**
	 * @return string
	 */
	public function getLogPath()
	{
		$path = $this->getRuntimePath().'logs/';

		if (!IOHelper::folderExists($path))
			IOHelper::createFolder($path, 0777);

		return $path;
	}

	/**
	 * @return string
	 */
	public function getStatePath()
	{
		$path = $this->getRuntimePath().'state/';

		if (!IOHelper::folderExists($path))
			IOHelper::createFolder($path, 0777);

		return $path;
	}

	/**
	 * @return string
	 */
	public function getComponentsPath()
	{
		return $this->getAppPath().'components/';
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
	 * @return string
	 */
	public function getMigrationsPath()
	{
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
		return BLOCKS_SITE_TRANSLATIONS_PATH;
	}

	/**
	 * @return string
	 */
	public function getCommandsPath()
	{
		return $this->getConsolePath().'commands/';
	}

	/**
	 * @return string
	 */
	public function getConsolePath()
	{
		return $this->getAppPath().'console/';
	}

	/**
	 * Returns the current templates path, taking into account whether this is a CP or Site request.
	 *
	 * @return string
	 */
	public function getTemplatesPath()
	{
		if (!isset($this->_templatePath))
		{
			if (BLOCKS_CP_REQUEST)
				$this->_templatePath = $this->getCpTemplatesPath();
			else
				$this->_templatePath = $this->getSiteTemplatesPath();
		}

		return $this->_templatePath;
	}

	/**
	 * Sets the current templates path.
	 *
	 * @param string $path
	 */
	public function setTemplatesPath($path)
	{
		$this->_templatePath = $path;
	}

	/**
	 * Returns the @@@productDisplay@@@ CP templates path.
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
		return BLOCKS_SITE_TEMPLATES_PATH;
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
		if (($path = blx()->config->offlinePath) !== null)
			return substr($path, 0, strlen($path) - strlen(IOHelper::getFileName($path)));

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

		if (!IOHelper::folderExists($path))
			IOHelper::createFolder($path, 0777);

		return $path;
	}

	/**
	 * @return string
	 */
	public function getSessionPath()
	{
		$path = $this->getRuntimePath().'sessions/';

		if (!IOHelper::folderExists($path))
			IOHelper::createFolder($path, 0777);

		return $path;
	}

	/**
	 * @return string
	 */
	public function getCachePath()
	{
		$path = $this->getRuntimePath().'cache/';

		if (!IOHelper::folderExists($path))
			IOHelper::createFolder($path, 0777);

		return $path;
	}
}
