<?php
namespace Blocks;

/**
 * Plugin template variable
 */
class PluginVariable extends ComponentVariable
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
}
