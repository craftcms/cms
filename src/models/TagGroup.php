<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ElementType;

/**
 * TagGroup model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class TagGroup extends BaseModel
{
	// Traits
	// =========================================================================

	use \craft\app\base\FieldLayoutTrait;

	// Properties
	// =========================================================================

	/**
	 * @var The element type that tag groups' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = ElementType::Tag;

	// Public Methods
	// =========================================================================

	/**
	 * Use the translated tag group's name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t($this->name);
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
		return [
			'id'            => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'fieldLayoutId' => AttributeType::Number,
		];
	}
}
