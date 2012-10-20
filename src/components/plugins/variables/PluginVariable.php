<?php
namespace Blocks;

/**
 * Plugin template variable
 */
class PluginVariable extends BaseComponentVariable
{
	/**
	 * Returns the plugin's version.
	 *
	 * @return string
	 */
	public function version()
	{
		return $this->component->getVersion();
	}

	/**
	 * Returns the plugin's developer link.
	 *
	 * @return string
	 */
	public function developer()
	{
		$url = $this->component->getDeveloperUrl();
		$name = $this->component->getDeveloper();

		if ($url)
		{
			return '<a target="_blank" href="'.$url.'">'.$name.'</a>';
		}
		else
		{
			return $name;
		}
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
	 * Returns the plugin’s display name.
	 *
	 * @return string
	 */
	public function name()
	{
		return $this->component->getName();
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
