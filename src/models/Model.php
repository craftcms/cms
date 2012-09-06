<?php
namespace Blocks;

/**
 *
 */
class Model extends BaseModel
{
	private $_attributes;

	/**
	 * Constructor
	 *
	 * @param array $attributes
	 */
	function __construct($attributes)
	{
		$this->_attributes = $attributes;
		parent::__construct();
	}

	/**
	 * Defines this model's attributes.
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return $this->_attributes;
	}
}
