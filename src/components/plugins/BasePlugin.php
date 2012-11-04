<?php
namespace Blocks;

/**
 * Plugin base class
 */
abstract class BasePlugin extends BaseComponent
{
	/**
	 * The type of component this is.
	 *
	 * @access protected
	 * @var string
	 */
	protected $componentType = 'Plugin';

	/**
	 * Returns the plugin’s version.
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function getVersion();

	/**
	 * Returns the plugin developer's name.
	 *
	 * @return string
	 */
	abstract public function getDeveloper();

	/**
	 * Returns the plugin developer's URL.
	 *
	 * @return string
	 */
	abstract public function getDeveloperUrl();

	/**
	 * Returns whether this plugin has its own section in the CP.
	 *
	 * @return bool
	 */
	public function hasCpSection()
	{
		return false;
	}

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
