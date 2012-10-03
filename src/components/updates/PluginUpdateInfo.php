<?php
namespace Blocks;

/**
 * Stores the available plugin update info.
 */
class PluginUpdateInfo
{
	public $class;
	public $localVersion;
	public $latestVersion;
	public $latestDate;
	public $status;
	public $displayName;
	public $notes;
	public $criticalUpdateAvailable;
	public $releases = array();

	/**
	 * @param array $properties
	 */
	function __construct($properties = array())
	{
		$this->class = isset($properties['class']) ? $properties['class'] : null;
		$this->localVersion = isset($properties['localVersion']) ? $properties['localVersion'] : null;
		$this->latestVersion = isset($properties['latestVersion']) ? $properties['latestVersion'] : null;
		$this->latestDate = isset($properties['latestDate']) ? $properties['latestDate'] : null;
		$this->status = isset($properties['status']) ? $properties['status'] : null;
		$this->displayName = isset($properties['displayName']) ? $properties['displayName'] : null;
		$this->notes = isset($properties['notes']) ? $properties['notes'] : null;
		$this->criticalUpdateAvailable = isset($properties['criticalUpdateAvailable']) ? $properties['criticalUpdateAvailable'] : null;

		if (isset($properties['releases']))
		{
			foreach ($properties['releases'] as $release)
			{
				$this->releases[] = new PluginNewReleaseInfo($release);
			}
		}
	}
}
