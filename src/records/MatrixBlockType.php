<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;
use craft\app\enums\AttributeType;

/**
 * Class MatrixBlockType record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlockType extends BaseRecord
{
	// Properties
	// =========================================================================

	/**
	 * Whether the Name and Handle attributes should validated to ensure they’re unique.
	 *
	 * @var bool
	 */
	public $validateUniques = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%matrixblocktypes}}';
	}

	/**
	 * Returns the matrix block type’s field.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getField()
	{
		return $this->hasOne(Field::className(), ['id' => 'fieldId']);
	}

	/**
	 * Returns the matrix block type’s fieldLayout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFieldLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'fieldLayoutId']);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['name', 'fieldId'], 'unique' => true],
			['columns' => ['handle', 'fieldId'], 'unique' => true],
		];
	}

	/**
	 * @inheritDoc BaseRecord::rules()
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		if (!$this->validateUniques)
		{
			foreach ($rules as $i => $rule)
			{
				if ($rule[1] == 'unique' && isset($rule['targetAttribute']) && is_array($rule['targetAttribute']) && count($rule['targetAttribute']) > 1)
				{
					unset($rules[$i]);
				}
			}
		}

		return $rules;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'name'       => [AttributeType::Name, 'required' => true],
			'handle'     => [AttributeType::Handle, 'required' => true],
			'sortOrder'  => AttributeType::SortOrder,
		];
	}
}
