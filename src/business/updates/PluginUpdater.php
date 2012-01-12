<?php

/**
 *
 */
class PluginUpdater implements IUpdater
{
	private $_pluginHandle = null;

	/**
	 * @param $pluginHandle
	 */
	function __construct($pluginHandle)
	{
		$this->_pluginHandle = $pluginHandle;
	}

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
