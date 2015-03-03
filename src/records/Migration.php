<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class Migration record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Migration extends ActiveRecord
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
		return '{{%migrations}}';
	}

	/**
	 * Returns the migration’s plugin.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getPlugin()
	{
		return $this->hasOne(Plugin::className(), ['id' => 'pluginId']);
	}

	/**
	 * @inheritDoc ActiveRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['version'], 'unique' => true],
		];
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
			'version' => [AttributeType::String, 'column' => ColumnType::Varchar, 'maxLength' => 255, 'required' => true],
			'applyTime' => [AttributeType::DateTime, 'required' => true],
		];
	}
}
