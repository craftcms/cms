<?php
namespace Blocks;

/**
 *
 */
class PluginUpdateData
{
	public $handle = null;
	public $localVersion = null;
	public $latestVersion = null;
	public $status = null;
	public $displayName = null;
	public $notes = null;
	public $criticalUpdateAvailable = null;
	public $newerReleases = null;

	/**
	 * @param null $properties
	 */
	function __construct($properties = null)
	{
		if ($properties == null)
			return;

		$this->handle = isset($properties['handle']) ? $properties['handle'] : null;
		$this->localVersion = isset($properties['localVersion']) ? $properties['localVersion'] : null;
		$this->latestVersion = isset($properties['latestVersion']) ? $properties['latestVersion'] : null;
		$this->status = isset($properties['status']) ? $properties['status'] : null;
		$this->displayName = isset($properties['displayName']) ? $properties['displayName'] : null;
		$this->notes = isset($properties['notes']) ? $properties['notes'] : null;
		$this->criticalUpdateAvailable = isset($properties['criticalUpdateAvailable']) ? $properties['criticalUpdateAvailable'] : null;

		if (isset($properties['newerReleases']))
			foreach ($properties['newerReleases'] as $pluginReleaseData)
				$this->newerReleases[] = new PluginNewReleaseUpdateData($pluginReleaseData);
	}
}
