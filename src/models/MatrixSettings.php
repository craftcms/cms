<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\fields\Matrix;

/**
 * MatrixSettings model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixSettings extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var integer Max blocks
	 */
	public $maxBlocks;


	/**
	 * @var Matrix|null
	 */
	private $_matrixField;

	/**
	 * @var
	 */
	private $_blockTypes;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['maxBlocks'], 'number', 'min' => -2147483648, 'max' => 2147483647, 'integerOnly' => true],
			[['maxBlocks'], 'safe', 'on' => 'search'],
		];
	}

	/**
	 * Constructor
	 *
	 * @param Matrix|null $matrixField
	 *
	 * @return MatrixSettings
	 */
	public function __construct(Matrix $matrixField = null)
	{
		$this->_matrixField = $matrixField;
	}

	/**
	 * Returns the field associated with this.
	 *
	 * @return Matrix|null
	 */
	public function getField()
	{
		return $this->_matrixField;
	}

	/**
	 * Returns the block types.
	 *
	 * @return array
	 */
	public function getBlockTypes()
	{
		if (!isset($this->_blockTypes))
		{
			if (!empty($this->_matrixField->id))
			{
				$this->_blockTypes = Craft::$app->getMatrix()->getBlockTypesByFieldId($this->_matrixField->id);
			}
			else
			{
				$this->_blockTypes = [];
			}
		}

		return $this->_blockTypes;
	}

	/**
	 * Sets the block types.
	 *
	 * @param array $blockTypes
	 *
	 * @return null
	 */
	public function setBlockTypes($blockTypes)
	{
		$this->_blockTypes = $blockTypes;
	}

	/**
	 * Validates all of the attributes for the current Model. Any attributes that fail validation will additionally get
	 * logged to the `craft/storage/logs` folder as a warning.
	 *
	 * In addition, we validate the block type settings.
	 *
	 * @param array|null $attributes
	 * @param bool       $clearErrors
	 *
	 * @return bool
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		// Enforce $clearErrors without copying code if we don't have to
		$validates = parent::validate($attributes, $clearErrors);

		if (!Craft::$app->getMatrix()->validateFieldSettings($this))
		{
			$validates = false;
		}

		return $validates;
	}
}
