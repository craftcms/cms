<?php
namespace Blocks;

/**
 *
 */
class PluginNewReleaseInfo
{
	public $version;
	public $date;
	public $notes;
	public $critical;

	/**
	 * @param null $properties
	 */
	function __construct($properties = array())
	{
		$this->version = isset($properties['version']) ? $properties['version'] : null;
		$this->critical = isset($properties['critical']) ? $properties['critical'] : null;
		$this->date = isset($properties['date']) ? $properties['date'] : null;
		$this->notes = isset($properties['notes']) ? $properties['notes'] : null;
	}
}
