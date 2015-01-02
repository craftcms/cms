<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\enums\ConfigFile;

/**
 * Class Config variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Config
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns whether a config item exists.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return craft()->config->exists($name, ConfigFile::General);
	}

	/**
	 * Returns a config item.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function __get($name)
	{
		return craft()->config->get($name, ConfigFile::General);
	}

	/**
	 * Returns a config item from the specified config file.
	 *
	 * @param string $name
	 * @param string $file
	 *
	 * @return mixed
	 */
	public function get($name, $file = 'general')
	{
		return craft()->config->get($name, $file);
	}

	/**
	 * Returns whether generated URLs should be formatted using PATH_INFO.
	 *
	 * @return bool
	 */
	public function usePathInfo()
	{
		return craft()->config->usePathInfo();
	}

	/**
	 * Returns whether generated URLs should omit 'index.php'.
	 *
	 * @return bool
	 */
	public function omitScriptNameInUrls()
	{
		return craft()->config->omitScriptNameInUrls();
	}

	/**
	 * Returns the CP resource trigger word.
	 *
	 * @return string
	 */
	public function getResourceTrigger()
	{
		return craft()->config->getResourceTrigger();
	}
}
