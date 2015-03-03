<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class EmailMessage record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EmailMessage extends ActiveRecord
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
		return '{{%emailmessages}}';
	}

	/**
	 * Returns the email message’s locale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'locale']);
	}

	/**
	 * @inheritDoc ActiveRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['key', 'locale'], 'unique' => true],
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
			'key'      => [AttributeType::String, 'required' => true, 'maxLength' => 150, 'column' => ColumnType::Char],
			'locale'   => [AttributeType::Locale, 'required' => true],
			'subject'  => [AttributeType::String, 'required' => true, 'maxLength' => 1000],
			'body'     => [AttributeType::String, 'required' => true, 'column' => ColumnType::Text],
		];
	}
}
