<?php

class PluginUpdater implements IUpdater
{
	private $_pluginhandle = null;

	function __construct($pluginHandle)
	{
		$this->_pluginhandle = $pluginHandle;
	}

	public function checkRequirements()
	{

	}

	public function start()
	{
		return true;
	}
}
