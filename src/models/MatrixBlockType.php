<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\base\Field;
use craft\app\base\FieldInterface;
use craft\app\base\Model;

/**
 * MatrixBlockType model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlockType extends Model
{
	// Traits
	// =========================================================================

	use \craft\app\base\FieldLayoutTrait;

	// Properties
	// =========================================================================

	/**
	 * @var integer ID
	 */
	public $id;

	/**
	 * @var integer Field ID
	 */
	public $fieldId;

	/**
	 * @var string Field layout ID
	 */
	public $fieldLayoutId;

	/**
	 * @var string Name
	 */
	public $name;

	/**
	 * @var string Handle
	 */
	public $handle;

	/**
	 * @var integer Sort order
	 */
	public $sortOrder;


	/**
	 * @var The element type that block types' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = 'craft\app\elements\MatrixBlock';

	/**
	 * @var bool
	 */
	public $hasFieldErrors = false;

	/**
	 * @var
	 */
	private $_fields;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['id'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['fieldId'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['sortOrder'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['id', 'fieldId', 'fieldLayoutId', 'name', 'handle', 'sortOrder'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Use the block type handle as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->handle;
	}

	/**
	 * Returns whether this is a new component.
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return (!$this->id || strncmp($this->id, 'new', 3) === 0);
	}

	/**
	 * Returns the fields associated with this block type.
	 *
	 * @return FieldInterface[]|Field[]
	 */
	public function getFields()
	{
		return $this->getFieldLayout()->getFields();
	}

	/**
	 * Sets the fields associated with this block type.
	 *
	 * @param FieldInterface[]|Field[] $fields
	 *
	 * @return null
	 */
	public function setFields($fields)
	{
		$this->getFieldLayout()->setFields($fields);
	}
}
