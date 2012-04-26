<?php
namespace Blocks;

/**
 * Stores all of the available update info.
 */
class UpdateInfo
{
	public $blocks;
	public $plugins = array();

	/**
	 * @param array $properties
	 */
	function __construct($properties = array())
	{
		if (isset($properties['blocks']))
			$this->blocks = new BlocksUpdateInfo($properties['blocks']);
		else
			$this->blocks = new BlocksUpdateInfo;

		if (isset($properties['plugins']))
			foreach ($properties['plugins'] as $pluginData)
				$this->plugins[$pluginData['class']] = new PluginUpdateInfo($pluginData);
	}
}
