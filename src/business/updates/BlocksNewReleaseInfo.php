<?php
namespace Blocks;

/**
 *
 */
class BlocksNewReleaseInfo
{
	public $version = null;
	public $build = null;
	public $date = null;
	public $notes = null;
	public $type = null;
	public $critical = null;
	public $manualUpdateRequired = null;

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
		$this->date = isset($properties['date']) ? $properties['date'] : null;
		$this->notes = isset($properties['notes']) ? $properties['notes'] : null;
		$this->type = isset($properties['type']) ? $properties['type'] : null;
		$this->manualUpdateRequired = isset($properties['manualUpdateRequired']) ? $properties['manualUpdateRequired'] : null;
	}
}
