<?php
namespace Blocks;

/**
 *
 */
class UpdateInfo
{

	public $plugins = null;
	public $blocks = null;

	/**
	 * @param null $properties
	 */
	function __construct($properties = null)
	{
		if ($properties == null)
		{
			$this->blocks = new BlocksUpdateInfo();
			return;
		}

		$this->blocks = isset($properties['blocks']) ? new BlocksUpdateInfo($properties['blocks']) : null;

		if (isset($properties['plugins']) && count($properties['plugins']) > 0)
			foreach ($properties['plugins'] as $pluginData)
				$this->plugins[$pluginData['handle']] = new PluginUpdateInfo($pluginData);
	}
}
