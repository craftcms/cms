<?php

class PluginUpdater implements IUpdater
{
	private $_pluginHandle = null;

	function __construct($pluginHandle)
	{
		$this->_pluginHandle = $pluginHandle;
	}

	public function checkRequirements()
	{

	}

	public function start()
	{
		return true;
	}
}
