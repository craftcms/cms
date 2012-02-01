<?php
namespace Blocks;

/**
 *
 */
class BlocksNewReleaseUpdateInfo
{
	public $version = null;
	public $build = null;
	public $releaseDate = null;
	public $releaseNotes = null;
	public $type = null;
	public $critical = null;

	/**
	 * @param null $properties
	 */
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
