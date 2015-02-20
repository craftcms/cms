<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class Plugin record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Plugin extends BaseRecord
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
		return '{{%plugins}}';
	}

	/**
	 * Returns the plugin’s migrations.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getMigrations()
	{
		return $this->hasMany(Migration::className(), ['pluginId' => 'id']);
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
			'class'       => [AttributeType::ClassName, 'required' => true],
			'version'     => ['maxLength' => 15, 'column' => ColumnType::Char, 'required' => true],
			'enabled'     => AttributeType::Bool,
			'settings'    => AttributeType::Mixed,
			'installDate' => [AttributeType::DateTime, 'required' => true],
		];
	}
}
