<?php
namespace Craft;

/**
 * Class StructureModel
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     2.0
 */
class StructureModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns whether elements in this structure can be sorted by the current user.
	 *
	 * @return bool
	 */
	public function isSortable()
	{
		return (!$this->movePermission || craft()->userSession->checkPermission($this->movePermission));
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
		return array(
			'id'             => AttributeType::Number,
			'maxLevels'      => AttributeType::Number,
			'movePermission' => AttributeType::String,
		);
	}
}
