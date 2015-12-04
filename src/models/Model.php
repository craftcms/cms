<?php
namespace Craft;

/**
 * Class Model
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class Model extends BaseModel
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
	 * @return Model
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
