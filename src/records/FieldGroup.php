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
 * Class FieldGroup record.
 *
 * @property integer $id ID
 * @property string $name Name
 * @property ActiveQueryInterface $fields Fields
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldGroup extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name'], 'unique'],
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
		return '{{%fieldgroups}}';
	}

	/**
	 * Returns the field group’s fields.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getFields()
	{
		return $this->hasMany(Field::className(), ['groupId' => 'id']);
	}
}
