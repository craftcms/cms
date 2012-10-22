<?php
namespace Blocks;

/**
 * Plugin template variable
 */
class PluginVariable extends BaseComponentVariable
{
	/**
	 * Returns the plugin’s display name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->component->getName();
	}

	/**
	 * Returns the plugin's version.
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return $this->component->getVersion();
	}

	/**
	 * Returns the plugin's developer.
	 *
	 * @return string
	 */
	public function getDeveloper()
	{
		return $this->component->getDeveloper();
	}

	/**
	 * Returns the plugin's developer's URL.
	 *
	 * @return string
	 */
	public function getDeveloperUrl()
	{
		return $this->component->getDeveloperUrl();
	}

	/**
	 * Returns whether the plugin is installed.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		return $this->component->isInstalled();
	}

	/**
	 * Returns if the plugin is currently enabled or not.
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->component->isEnabled();
	}
}
