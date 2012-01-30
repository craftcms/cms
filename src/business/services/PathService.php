<?php
namespace Blocks;

/**
 *
 */
class PathService extends \CApplicationComponent
{
	/* Paths */

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
	public function getTemplatesPath()
	{
		return BLOCKS_TEMPLATES_PATH;
	}

	/**
	 * @return string
	 */
	public function getResourcesPath()
	{
		return $this->appPath.'resources/';
	}

	/**
	 * @return mixed
	 */
	public function getFrameworkPath()
	{
		return $this->appPath.'framework/';
	}

	/**
	 * @return string
	 */
	public function getCPTemplatePath()
	{
		return $this->appPath.'templates/';
	}

	/**
	 * @return string
	 */
	public function getMigrationsPath()
	{
		return $this->appPath.'migrations/';
	}

	/**
	 * @return string
	 */
	public function getModelsPath()
	{
		return $this->appPath.'models/';
	}

	/**
	 * @return string
	 */
	public function getCommandsPath()
	{
		return $this->appPath.'commands/';
	}

	/**
	 * @return string
	 */
	public function getSiteTemplatePath()
	{
		$siteHandle = Blocks::app()->site->currentSiteByUrl;
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return $this->templatesPath.'site_templates/'.$siteHandle.'/';
	}

	/**
	 * @return string
	 */
	public function getEmailTemplatePath()
	{
		return $this->templatesPath.'email_templates/';
	}

	/**
	 * @return string
	 */
	public function getTemplatePath()
	{
		// site request
		if (BLOCKS_CP_REQUEST !== true)
			return $this->siteTemplatePath;

		// CP request
		return $this->cpTemplatePath;
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

		return $cachePath;
	}

	/**
	 * @return string
	 */
	public function getEmailTemplateCachePath()
	{
		$cachePath = $this->runtimePath.'parsed_templates/custom/email_templates/';

		if (!is_dir($cachePath))
			mkdir($cachePath, 0777, true);

		return $cachePath;
	}

	/**
	 * @return string
	 */
	public function getSessionPath()
	{
		$path = $this->runtimePath.'sessions/';

		if (!is_dir($path))
			mkdir($path, 0777, true);

		return $path;
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
