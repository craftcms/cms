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
 * Stores entry drafts.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryDraft extends ActiveRecord
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
		return '{{%entrydrafts}}';
	}

	/**
	 * Returns the entry draft’s entry.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getEntry()
	{
		return $this->hasOne(Entry::className(), ['id' => 'entryId']);
	}

	/**
	 * Returns the entry draft’s section.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSection()
	{
		return $this->hasOne(Section::className(), ['id' => 'sectionId']);
	}

	/**
	 * Returns the entry draft’s creator.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getCreator()
	{
		return $this->hasOne(User::className(), ['id' => 'creatorId']);
	}

	/**
	 * Returns the entry draft’s locale.
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
			['columns' => ['entryId', 'locale']],
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
			'locale' => [AttributeType::Locale, 'required' => true],
			'name'   => [AttributeType::String, 'required' => true],
			'notes'  => [AttributeType::String, 'column' => ColumnType::TinyText],
			'data'   => [AttributeType::Mixed, 'required' => true, 'column' => ColumnType::MediumText],
		];
	}
}
