<?php
namespace Blocks;

/**
 *
 */
class PluginNewReleaseInfo
{
	public $version = null;
	public $date = null;
	public $notes = null;
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
		$this->date = isset($properties['date']) ? $properties['date'] : null;
		$this->notes = isset($properties['notes']) ? $properties['notes'] : null;
	}
}
