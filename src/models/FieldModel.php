<?php
namespace Craft;

/**
 * Field model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class FieldModel extends BaseComponentModel
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_fieldType;

	// Public Methods
	// =========================================================================

	/**
	 * Use the translated field name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * Returns whether this field has a column in the content table.
	 *
	 * @return bool
	 */
	public function hasContentColumn()
	{
		$fieldType = $this->getFieldType();

		return ($fieldType && $fieldType->defineContentAttribute());
	}

	/**
	 * Returns the field type this field is using.
	 *
	 * @return BaseFieldType|null
	 */
	public function getFieldType()
	{
		if (!isset($this->_fieldType))
		{
			$this->_fieldType = craft()->fields->populateFieldType($this);

			// Might not actually exist
			if (!$this->_fieldType)
			{
				$this->_fieldType = false;
			}
		}

		// Return 'null' instead of 'false' if it doesn't exist
		if ($this->_fieldType)
		{
			return $this->_fieldType;
		}
	}

	/**
	 * Returns the field's group.
	 *
	 * @return UserGroupModel
	 */
	public function getGroup()
	{
		return craft()->fields->getGroupById($this->groupId);
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
		return array_merge(parent::defineAttributes(), array(
			'groupId'      => AttributeType::Number,
			'name'         => AttributeType::String,
			'handle'       => AttributeType::String,
			'context'      => AttributeType::String,
			'instructions' => AttributeType::String,
			'required'     => AttributeType::Bool,
			'translatable' => AttributeType::Bool,

			'oldHandle'    => AttributeType::String,
			'columnPrefix' => AttributeType::String,
		));
	}
}
