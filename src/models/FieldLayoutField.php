<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\base\Model;
use craft\app\models\FieldLayout as FieldLayoutModel;

/**
 * FieldLayoutField model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutField extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Layout ID
	 */
	public $layoutId;

	/**
	 * @var integer Tab ID
	 */
	public $tabId;

	/**
	 * @var string Field ID
	 */
	public $fieldId;

	/**
	 * @var boolean Required
	 */
	public $required = false;

	/**
	 * @var string Sort order
	 */
	public $sortOrder;


	/**
	 * @var
	 */
	private $_layout;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['layoutId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['tabId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['fieldId'], 'string', 'max' => 255],
			[['sortOrder'], 'string', 'max' => 4],
			[['id', 'layoutId', 'tabId', 'fieldId', 'required', 'sortOrder'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Returns the field’s layout.
	 *
	 * @return FieldLayoutModel|null The field’s layout.
	 */
	public function getLayout()
	{
		if (!isset($this->_layout))
		{
			if ($this->layoutId)
			{
				$this->_layout = Craft::$app->fields->getLayoutById($this->layoutId);
			}
			else
			{
				$this->_layout = false;
			}
		}

		if ($this->_layout)
		{
			return $this->_layout;
		}
	}

	/**
	 * Sets the field’s layout.
	 *
	 * @param FieldLayoutModel $layout The field’s layout.
	 *
	 * @return null
	 */
	public function setLayout(FieldLayoutModel $layout)
	{
		$this->_layout = $layout;
	}

	/**
	 * Returns the associated field.
	 *
	 * @return FieldInterface|Field|null The associated field.
	 */
	public function getField()
	{
		if ($this->fieldId)
		{
			return Craft::$app->fields->getFieldById($this->fieldId);
		}
	}
}
