<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\enums\AttributeType;
use craft\app\fieldtypes\BaseFieldType;
use craft\app\models\FieldGroup as FieldGroupModel;

/**
 * Field model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Field extends BaseComponentModel
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var string Type
	 */
	public $type;

	/**
	 * @var array Settings
	 */
	public $settings;

	/**
	 * @var integer Group ID
	 */
	public $groupId;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Handle
	 */
	public $handle;

	/**
	 * @var string Context
	 */
	public $context;

	/**
	 * @var string Instructions
	 */
	public $instructions;

	/**
	 * @var boolean Required
	 */
	public $required = false;

	/**
	 * @var boolean Translatable
	 */
	public $translatable = false;

	/**
	 * @var string Old handle
	 */
	public $oldHandle;

	/**
	 * @var string Column prefix
	 */
	public $columnPrefix;


	/**
	 * @var
	 */
	private $_fieldType;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['groupId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'type', 'settings', 'groupId', 'name', 'handle', 'context', 'instructions', 'required', 'translatable', 'oldHandle', 'columnPrefix'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Use the translated field name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t('app', $this->name);
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
			$this->_fieldType = Craft::$app->fields->populateFieldType($this);

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
	 * @return FieldGroupModel
	 */
	public function getGroup()
	{
		return Craft::$app->fields->getGroupById($this->groupId);
	}
}
