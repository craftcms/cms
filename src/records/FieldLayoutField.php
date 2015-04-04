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
 * Class FieldLayoutField record.
 *
 * @property integer $id ID
 * @property integer $layoutId Layout ID
 * @property integer $tabId Tab ID
 * @property integer $fieldId Field ID
 * @property boolean $required Required
 * @property string $sortOrder Sort order
 * @property ActiveQueryInterface $layout Layout
 * @property ActiveQueryInterface $tab Tab
 * @property ActiveQueryInterface $field Field
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutField extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['layoutId'], 'unique', 'targetAttribute' => ['layoutId', 'fieldId']],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%fieldlayoutfields}}';
	}

	/**
	 * Returns the field layout field’s layout.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLayout()
	{
		return $this->hasOne(FieldLayout::className(), ['id' => 'layoutId']);
	}

	/**
	 * Returns the field layout field’s tab.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getTab()
	{
		return $this->hasOne(FieldLayoutTab::className(), ['id' => 'tabId']);
	}

	/**
	 * Returns the field layout field’s field.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getField()
	{
		return $this->hasOne(Field::className(), ['id' => 'fieldId']);
	}
}
