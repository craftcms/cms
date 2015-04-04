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
 * Field layout record class.
 *
 * @property integer $id ID
 * @property string $type Type
 * @property ActiveQueryInterface $tabs Tabs
 * @property ActiveQueryInterface $fields Fields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayout extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['type'], 'required'],
			[['type'], 'string', 'max' => 150],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%fieldlayouts}}';
	}

	/**
	 * Returns the field layout’s tabs.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getTabs()
	{
		return $this->hasMany(FieldLayoutTab::className(), ['layoutId' => 'id']);
	}

	/**
	 * Returns the field layout’s fields.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFields()
	{
		return $this->hasMany(FieldLayoutField::className(), ['layoutId' => 'id']);
	}
}
