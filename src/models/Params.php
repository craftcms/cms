<?php
namespace craft\models;

/**
 * Settings model.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
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
