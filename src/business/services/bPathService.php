<?php

/**
 *
 */
class bPathService extends CApplicationComponent
{
	/* Paths */

	/**
	 * @return string
	 */
	public function getBasePath()
	{
		return $this->normalizeDirectorySeparators(BLOCKS_BASE_PATH);
	}

	/**
	 * @return string
	 */
	public function getConfigPath()
	{
		return $this->normalizeDirectorySeparators($this->basePath.'config/');
	}

	/**
	 * @return string
	 */
	public function getBlocksConfigPath()
	{
		return $this->normalizeDirectorySeparators($this->configPath.'blocks.php');
	}

	/**
	 * @return string
	 */
	public function getPluginsPath()
	{
		return $this->normalizeDirectorySeparators($this->basePath.'plugins/');
	}

	/**
	 * @return string
	 */
	public function getResourcesPath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'resources/');
	}

	/**
	 * @return string
	 */
	public function getAppPath()
	{
		return $this->normalizeDirectorySeparators(Blocks::app()->basePath.'/');
	}

	/**
	 * @return mixed
	 */
	public function getFrameworkPath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'framework/');
	}

	/**
	 * @return string
	 */
	public function getRuntimePath()
	{
		return $this->normalizeDirectorySeparators(Blocks::app()->runtimePath.'/');
	}

	/**
	 * @return string
	 */
	public function getCPTemplatePath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'templates/');
	}

	/**
	 * @return string
	 */
	public function getMigrationsPath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'migrations/');
	}

	/**
	 * @return string
	 */
	public function getCommandsPath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'commands/');
	}

	/**
	 * @return string
	 */
	public function getSiteTemplatePath()
	{
		$siteHandle = Blocks::app()->site->currentSiteByUrl;
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return $this->normalizeDirectorySeparators($this->basePath.'templates/site_templates/'.$siteHandle.'/');
	}

	/**
	 * @return string
	 */
	public function getEmailTemplatePath()
	{
		return $this->normalizeDirectorySeparators($this->basePath.'templates/email_templates/');
	}

	/**
	 * @return string
	 */
	public function getTemplatePath()
	{
		// site request
		if (BLOCKS_CP_REQUEST !== true)
			$templatePath = $this->siteTemplatePath;
		else
			// CP request
			$templatePath = $this->cpTemplatePath;

		return $this->normalizeDirectorySeparators($templatePath);
	}

	/**
	 * @return string
	 */
	public function getSiteTemplateCachePath()
	{
		$cachePath = null;

		if (BLOCKS_CP_REQUEST !== true)
		{
			$siteHandle = Blocks::app()->site->currentSiteByUrl;
			$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;
			$cachePath = $this->runtimePath.'parsed_templates/custom/site_templates/'.$siteHandle.'/';
		}
		else
		{
			$cachePath = $this->runtimePath.'parsed_templates/cp/';
		}

		if (!is_dir($cachePath))
			mkdir($cachePath, 0777, true);

		return $this->normalizeDirectorySeparators($cachePath);
	}

	/**
	 * @return string
	 */
	public function getEmailTemplateCachePath()
	{
		$cachePath = $this->runtimePath.'parsed_templates/email_templates/';

		if (!is_dir($cachePath))
			mkdir($cachePath, 0777, true);

		return $this->normalizeDirectorySeparators($cachePath);
	}

	/**
	 * @return string
	 */
	public function getSessionPath()
	{
		$path = $this->runtimePath.'sessions/';

		if (!is_dir($path))
			mkdir($path, 0777, true);

		return $this->normalizeDirectorySeparators($path);
	}

	/**
	 * @param $path
	 * @return mixed
	 */
	public function normalizeDirectorySeparators($path)
	{
		return str_replace('\\', '/', $path);
	}

	/**
	 * Adds a trailing slash to the end of a path if one does not exist
	 * @param $path The path to normalize.
	 * @return string The normalized path.
	 */
	public function normalizeTrailingSlash($path)
	{
		$path = rtrim($path, '\\/').'/';
		return $path;
	}
}
