<?php
namespace Blocks;

/**
 *
 */
class PluginUpdater
{
	private $_pluginHandle;

	/**
	 * @param $pluginHandle
	 */
	function __construct($pluginHandle)
	{
		$this->_pluginHandle = $pluginHandle;
	}

	/**
	 *
	 */
	public function checkRequirements()
	{

	}

	/**
	 * @return bool
	 */
	public function start()
	{
		return true;
	}
}
