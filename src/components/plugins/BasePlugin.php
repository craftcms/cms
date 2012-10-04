<?php
namespace Blocks;

/**
 * Plugin base class
 */
abstract class BasePlugin extends BaseComponent
{
	public $hasCpSection = false;

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
	 * @return string
	 */
	abstract public function getDeveloper();

	/**
	 * Returns the plugin’s developer URL.
	 *
	 * @return string
	 */
	abstract public function getDeveloperUrl();

	/**
	 * Returns whether the plugin is installed.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		if (isset($this->model))
		{
			return true;
		}

		$class = $this->getClassHandle();
		$installed = blx()->plugins->isPluginInstalled($class);
		return $installed;
	}

	/**
	 * Return if a plugin is enabled or not.
	 * @return bool
	 */
	public function isEnabled()
	{
		if (!isset($this->model))
		{
			return false;
		}

		return $this->model->enabled == 1 ? true : false;
	}
}
