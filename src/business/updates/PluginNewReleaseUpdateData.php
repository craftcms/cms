<?php
namespace Blocks;

/**
 *
 */
class PluginNewReleaseUpdateData
{
	public $version = null;
	public $releaseDate = null;
	public $releaseNotes = null;
	public $critical = null;

	/**
	 * @param null $properties
	 */
	function __construct($properties = null)
	{
		if ($properties == null)
			return;

		$this->version = isset($properties['version']) ? $properties['version'] : null;
		$this->critical = isset($properties['critical']) ? $properties['critical'] : null;
		$this->releaseDate = isset($properties['releaseDate']) ? $properties['releaseDate'] : null;
		$this->releaseNotes = isset($properties['releaseNotes']) ? $properties['releaseNotes'] : null;
	}
}
