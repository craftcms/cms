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
	public function getModelsPath()
	{
		return $this->getAppPath().'models/';
	}

	/**
	 * @return string
	 */
	public function getTranslationsPath()
	{
		return $this->getAppPath().'translations/';
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
		return $this->getAppPath().'business/console/';
	}

	/**
	 * Returns the current templates path, taking into account whether this is a CP or Site request.
	 * @return mixed
	 */
	public function getTemplatePath()
	{
		if (BLOCKS_CP_REQUEST)
			return $this->getAppTemplatesPath();
		else
			return $this->getSiteTemplatesPath();
	}

	/**
	 * Returns the Blocks app templates path.
	 * @return string
	 */
	public function getAppTemplatesPath()
	{
		return $this->getAppPath().'templates/';
	}

	/**
	 * Returns the current site's templates path, or null if there is no current site.
	 * @return mixed
	 */
	public function getSiteTemplatesPath()
	{
		$site = blx()->sites->getCurrentSite();
		if ($site)
			return BLOCKS_TEMPLATES_PATH.$site->handle.'/';
		else
			return null;
	}

	/**
	 * Returns the path to the offline template by first checking to see if they have set a custom path in config.
	 * If that is not set, it will fall back on the default CP offline template.
	 * @return mixed
	 */
	public function getOfflineTemplatePath()
	{
		// If the user has set offlinePath config item, let's use it.
		if (($path = blx()->config->offlinePath) !== null)
			return substr($path, 0, strlen($path) - strlen(pathinfo($path, PATHINFO_BASENAME)));

		return $this->getAppTemplatesPath();
	}

	/**
	 * Returns the current parsed templates path, taking into account whether this is a CP or Site request.
	 * @return mixed
	 */
	public function getParsedTemplatesPath()
	{
		if (BLOCKS_CP_REQUEST)
			return $this->getParsedAppTemplatesPath();
		else
			return $this->getParsedSiteTemplatesPath();
	}

	/**
	 * Returns the parsed Blocks app templates path.
	 * @return string
	 */
	public function getParsedAppTemplatesPath()
	{
		$path = $this->getRuntimePath().'parsed_templates/app/';

		if (!is_dir($path))
			mkdir($path, 0777, true);

		return $path;
	}

	/**
	 * Returns the current site's parsed templates path, or null if there is no current site.
	 * @return mixed
	 */
	public function getParsedSiteTemplatesPath()
	{
		$site = blx()->sites->getCurrentSite();
		if ($site)
		{
			$path = $this->getRuntimePath().'parsed_templates/sites/'.$site->handle.'/';

			if (!is_dir($path))
				mkdir($path, 0777, true);

			return $path;
		}
		else
			return null;
	}

	/**
	 * @return string
	 */
	public function getParsedPluginTemplatesPath()
	{
		$path = $this->getRuntimePath().'parsed_templates/plugins/';

		if (!is_dir($path))
			mkdir($path, 0777, true);

		return $path;
	}

	/**
	 * Returns the parsed email templates path.
	 * @return string
	 */
	public function getParsedEmailTemplatesPath()
	{
		$path = $this->getRuntimePath().'parsed_templates/email/';

		if (!is_dir($path))
			mkdir($path, 0777, true);

		return $path;
	}

	/**
	 * @return string
	 */
	public function getSessionPath()
	{
		$path = $this->getRuntimePath().'sessions/';

		if (!is_dir($path))
			mkdir($path, 0777, true);

		return $path;
	}

	/**
	 * @return string
	 */
	public function getStatePath()
	{
		$path = $this->getRuntimePath().'state/';

		if (!is_dir($path))
			mkdir($path, 0777, true);

		return $path;
	}

	/**
	 * @return string
	 */
	public function getCachePath()
	{
		$path = $this->getRuntimePath().'cache/';

		if (!is_dir($path))
			mkdir($path, 0777, true);

		return $path;
	}
}
