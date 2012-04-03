<?php
namespace Blocks;

/**
 *
 */
class BlocksUpdateInfo
{
	public $localBuild = null;
	public $localVersion = null;
	public $latestVersion = null;
	public $latestBuild = null;
	public $latestDate = null;
	public $criticalUpdateAvailable = null;
	public $manualUpdateRequired = null;
	public $versionUpdateStatus = null;
	public $releases = array();

	/**
	 * @param null $properties
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
