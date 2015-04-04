<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Class MatrixBlockType record.
 *
 * @property integer $id ID
 * @property integer $fieldId Field ID
 * @property integer $fieldLayoutId Field layout ID
 * @property string $name Name
 * @property string $handle Handle
 * @property string $sortOrder Sort order
 * @property ActiveQueryInterface $field Field
 * @property ActiveQueryInterface $fieldLayout Field layout
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlockType extends ActiveRecord
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
	 */
	public function rules()
	{
		return [
			[['handle'], 'craft\\app\\validators\\Handle', 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
			[['name'], 'unique', 'targetAttribute' => ['name', 'fieldId']],
			[['handle'], 'unique', 'targetAttribute' => ['handle', 'fieldId']],
			[['name', 'handle'], 'required'],
			[['name', 'handle'], 'string', 'max' => 255],
		];
	}

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
}
