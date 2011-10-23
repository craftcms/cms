<?php

interface IPluginRepository
{
	function getAllInstalledPlugins();
	function getAllInstalledPluginHandlesAndVersions();
}
