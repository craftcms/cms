<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

/**
 * Params model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Params extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var array
	 */
	private $_attributeDefs;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param array $attributeDefs
	 *
	 * @return Params
	 */
	public function __construct($attributeDefs)
	{
		$this->_attributeDefs = $attributeDefs;
		parent::__construct();
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return $this->_attributeDefs;
	}
}
