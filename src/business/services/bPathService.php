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
	public function getResourcesPath()
	{
		return BLOCKS_APP_PATH.'resources/';
	}

	/**
	 * @return mixed
	 */
	public function getFrameworkPath()
	{
		return BLOCKS_APP_PATH.'framework/';
	}

	/**
	 * @return string
	 */
	public function getCPTemplatePath()
	{
		return BLOCKS_APP_PATH.'templates/';
	}

	/**
	 * @return string
	 */
	public function getMigrationsPath()
	{
		return BLOCKS_APP_PATH.'migrations/';
	}

	/**
	 * @return string
	 */
	public function getCommandsPath()
	{
		return BLOCKS_APP_PATH.'commands/';
	}

	/**
	 * @return string
	 */
	public function getSiteTemplatePath()
	{
		$siteHandle = Blocks::app()->site->currentSiteByUrl;
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return BLOCKS_TEMPLATES_PATH.'site_templates/'.$siteHandle.'/';
	}

	/**
	 * @return string
	 */
	public function getEmailTemplatePath()
	{
		return BLOCKS_TEMPLATES_PATH.'email_templates/';
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
			$cachePath = BLOCKS_RUNTIME_PATH.'parsed_templates/custom/site_templates/'.$siteHandle.'/';
		}
		else
		{
			$cachePath = BLOCKS_RUNTIME_PATH.'parsed_templates/cp/';
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
		$cachePath = BLOCKS_RUNTIME_PATH.'parsed_templates/email_templates/';

		if (!is_dir($cachePath))
			mkdir($cachePath, 0777, true);

		return $cachePath;
	}

	/**
	 * @return string
	 */
	public function getSessionPath()
	{
		$path = BLOCKS_RUNTIME_PATH.'sessions/';

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
