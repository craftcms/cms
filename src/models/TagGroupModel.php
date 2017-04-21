<?php
namespace Craft;

/**
 * Tag group model.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     2.0
 */
class TagGroupModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Use the translated tag group's name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return (string)Craft::t($this->name);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior(ElementType::Tag),
		);
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
			'id'            => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'fieldLayoutId' => AttributeType::Number,
		);
	}
}
