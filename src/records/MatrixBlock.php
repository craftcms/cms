<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;

/**
 * Class MatrixBlock record.
 *
 * @var integer $id ID
 * @var integer $ownerId Owner ID
 * @var ActiveQueryInterface $ownerLocale Owner locale
 * @var integer $fieldId Field ID
 * @var integer $typeId Type ID
 * @var string $sortOrder Sort order
 * @var ActiveQueryInterface $element Element
 * @var ActiveQueryInterface $owner Owner
 * @var ActiveQueryInterface $field Field
 * @var ActiveQueryInterface $type Type

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MatrixBlock extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['ownerLocale'], 'craft\\app\\validators\\Locale'],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%matrixblocks}}';
	}

	/**
	 * Returns the matrix block’s element.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElement()
	{
		return $this->hasOne(Element::className(), ['id' => 'id']);
	}

	/**
	 * Returns the matrix block’s owner.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getOwner()
	{
		return $this->hasOne(Element::className(), ['id' => 'ownerId']);
	}

	/**
	 * Returns the matrix block’s ownerLocale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getOwnerLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'ownerLocale']);
	}

	/**
	 * Returns the matrix block’s field.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getField()
	{
		return $this->hasOne(Field::className(), ['id' => 'fieldId']);
	}

	/**
	 * Returns the matrix block’s type.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getType()
	{
		return $this->hasOne(MatrixBlockType::className(), ['id' => 'typeId']);
	}
}
