<?php
namespace Blocks;

/**
 *
 */
class Model extends BaseModel
{
	private $_properties;

	/**
	 * Constructor
	 *
	 * @param array $properties
	 */
	function __construct($properties)
	{
		$this->_properties = $properties;
		parent::__construct();
	}

	/**
	 * Returns a list of this model's properties.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return $this->_properties;
	}
}
