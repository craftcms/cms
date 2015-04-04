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
 * Field record class.
 *
 * @property integer $id ID
 * @property integer $layoutId Layout ID
 * @property string $name Name
 * @property string $sortOrder Sort order
 * @property ActiveQueryInterface $layout Layout
 * @property ActiveQueryInterface $fields Fields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutTab extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name'], 'required'],
			[['name'], 'string', 'max' => 255],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%fieldlayouttabs}}';
	}

	/**
	 * Returns the field layout tab’s layout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'layoutId']);
	}

	/**
	 * Returns the field layout tab’s fields.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFields()
	{
		return $this->hasMany(FieldLayoutField::className(), ['tabId' => 'id']);
	}
}
