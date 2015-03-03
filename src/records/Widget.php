<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;

/**
 * Class Widget record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Widget extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%widgets}}';
	}

	/**
	 * Returns the widget’s user.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getUser()
	{
		return $this->hasOne(User::className(), ['id' => 'userId']);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc ActiveRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'type'      => [AttributeType::ClassName, 'required' => true],
			'sortOrder' => AttributeType::SortOrder,
			'settings'  => AttributeType::Mixed,
			'enabled'   => [AttributeType::Bool, 'default' => true],
		];
	}
}
