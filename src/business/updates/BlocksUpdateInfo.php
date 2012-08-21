<?php
namespace Blocks;

/**
 * Stores the available @@@productDisplay@@@ update info.
 */
class BlocksUpdateInfo
{
	public $localBuild;
	public $localVersion;
	public $latestVersion;
	public $latestBuild;
	public $latestDate;
	public $criticalUpdateAvailable;
	public $manualUpdateRequired;
	public $versionUpdateStatus;
	public $releases = array();

	/**
	 * @param array $properties
	 */
	function __construct($properties = array())
	{
		$this->localBuild = isset($properties['localBuild']) ? $properties['localBuild'] : null;
		$this->localVersion = isset($properties['localVersion']) ? $properties['localVersion'] : null;
		$this->latestBuild = isset($properties['latestBuild']) ? $properties['latestBuild'] : null;
		$this->latestVersion = isset($properties['latestVersion']) ? $properties['latestVersion'] : null;
		$this->latestDate = isset($properties['latestDate']) ? $properties['latestDate'] : null;
		$this->criticalUpdateAvailable = isset($properties['criticalUpdateAvailable']) ? $properties['criticalUpdateAvailable'] : null;
		$this->manualUpdateRequired = isset($properties['manualUpdateRequired']) ? $properties['manualUpdateRequired'] : null;
		$this->versionUpdateStatus = isset($properties['versionUpdateStatus']) ? $properties['versionUpdateStatus'] : null;

		if (isset($properties['releases']) && count($properties['releases']) > 0)
			foreach ($properties['releases'] as $blocksNewReleaseData)
				$this->releases[] = new BlocksNewReleaseInfo($blocksNewReleaseData);;
	}
}
