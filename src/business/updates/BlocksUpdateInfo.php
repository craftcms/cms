<?php

class BlocksUpdateInfo
{
	public $localBuild = null;
	public $localVersion = null;
	public $latestVersion = null;
	public $latestBuild = null;
	public $criticalUpdateAvailable = null;
	public $versionUpdateStatus = null;
	public $plugins = null;
	public $newerReleases = null;

	function __construct($properties = null)
	{
		if ($properties == null)
			return;

		$this->localBuild = isset($properties['localBuild']) ? $properties['localBuild'] : null;
		$this->localVersion = isset($properties['localVersion']) ? $properties['localVersion'] : null;
		$this->latestVersion = isset($properties['latestVersion']) ? $properties['latestVersion'] : null;
		$this->latestBuild = isset($properties['latestBuild']) ? $properties['latestBuild'] : null;
		$this->criticalUpdateAvailable = isset($properties['criticalUpdateAvailable']) ? $properties['criticalUpdateAvailable'] : null;
		$this->versionUpdateStatus = isset($properties['versionUpdateStatus']) ? $properties['versionUpdateStatus'] : null;

		if (isset($properties['newerReleases']) && count($properties['newerReleases']) > 0)
			foreach ($properties['newerReleases'] as $blocksReleaseData)
				$this->newerReleases[] = new BlocksNewReleaseUpdateInfo($blocksReleaseData);;

		if (isset($properties['plugins']) && count($properties['plugins']) > 0)
			foreach ($properties['plugins'] as $pluginData)
				$this->plugins[$pluginData['handle']] = new PluginUpdateData($pluginData);
	}
}

class BlocksNewReleaseUpdateInfo
{
	public $version = null;
	public $build = null;
	public $releaseDate = null;
	public $releaseNotes = null;
	public $type = null;
	public $critical = null;

	function __construct($properties = null)
	{
		if ($properties == null)
			return;

		$this->version = isset($properties['version']) ? $properties['version'] : null;
		$this->build = isset($properties['build']) ? $properties['build'] : null;
		$this->critical = isset($properties['critical']) ? $properties['critical'] : null;
		$this->releaseDate = isset($properties['releaseDate']) ? $properties['releaseDate'] : null;
		$this->releaseNotes = isset($properties['releaseNotes']) ? $properties['releaseNotes'] : null;
		$this->type = isset($properties['type']) ? $properties['type'] : null;
	}
}
