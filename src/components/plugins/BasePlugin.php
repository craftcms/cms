<?php
namespace Blocks;

/**
 * Plugin base class
 */
abstract class BasePlugin extends BaseComponent implements IPlugin
{
	protected $componentType = 'Plugin';

	/**
	 * Returns the plugin’s version.
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function getVersion();

	/**
	 * Returns the plugin’s developer name.
	 *
	 * @return string|null
	 */
	public function getDeveloper()
	{
		return null;
	}

	/**
	 * Returns the plugin’s developer URL.
	 *
	 * @return string|null
	 */
	public function getDeveloperUrl()
	{
		return null;
	}

	/**
	 * Returns whether the plugin is installed.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		if (isset($this->record))
			return true;

		$class = $this->getClassHandle();
		$installed = blx()->plugins->isPluginInstalled($class);
		return $installed;
	}
}
