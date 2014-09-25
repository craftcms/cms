<?php
namespace Craft;

/**
 * Field group model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class FieldGroupModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Use the group name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->name;
	}

	/**
	 * Returns the group's fields.
	 *
	 * @return array
	 */
	public function getFields()
	{
		return craft()->fields->getFieldsByGroupId($this->id);
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
			'id'   => AttributeType::Number,
			'name' => AttributeType::Name,
		);
	}
}
