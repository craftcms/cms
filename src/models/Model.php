<?php
namespace Craft;

/**
 * Class Model
 *
 * @package craft.app.models
 */
class Model extends BaseModel
{
	private $_attributeDefs;

	/**
	 * Constructor
	 *
	 * @param array $attributeDefs
	 */
	function __construct($attributeDefs)
	{
		$this->_attributeDefs = $attributeDefs;
		parent::__construct();
	}

	/**
	 * Defines this model's attributeDefs.
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return $this->_attributeDefs;
	}
}
