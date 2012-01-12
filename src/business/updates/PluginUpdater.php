<?php

/**
 *
 */
class PluginUpdater implements IUpdater
{
	private $_pluginHandle = null;

	/**
	 * @access public
	 *
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
	 * @access public
	 *
	 * @return bool
	 */
	public function start()
	{
		return true;
	}
}
