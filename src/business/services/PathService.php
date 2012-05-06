<?php
namespace Blocks;

/**
 *
 */
class PathService extends Component
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
	public function getBlockTypesPath()
	{
		return $this->getAppPath().'blocktypes/';
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
			return $this->getCpTemplatesPath();
		else
			return $this->getSiteTemplatesPath();
	}

	/**
	 * Returns the CP templates path.
	 * @return string
	 */
	public function getCpTemplatesPath()
	{
		return $this->getAppPath().'templates/';
	}

	/**
	 * Returns the current site's templates path, or null if there is no current site.
	 * @return mixed
	 */
	public function getSiteTemplatesPath()
	{
		$site = b()->sites->current;
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
		if (($path = b()->config->offlinePath) !== null)
			return substr($path, 0, strlen($path) - strlen(pathinfo($path, PATHINFO_BASENAME)));

		return $this->getCpTemplatesPath();
	}

	/**
	 * Returns 
	 * @return string
	 */
	public function getEmailTemplatesPath()
	{
		return $this->getAppPath().'email_templates/';
	}

	/**
	 * Returns the current parsed templates path, taking into account whether this is a CP or Site request.
	 * @return mixed
	 */
	public function getParsedTemplatesPath()
	{
		if (BLOCKS_CP_REQUEST)
			return $this->getParsedCpTemplatesPath();
		else
			return $this->getParsedSiteTemplatesPath();
	}

	/**
	 * Returns the parsed CP templates path.
	 * @return string
	 */
	public function getParsedCpTemplatesPath()
	{
		$path = $this->getRuntimePath().'parsed_templates/cp/';

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
		$site = b()->sites->current;
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
