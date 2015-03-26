<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use Craft;
use craft\app\db\ActiveRecord;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class EntryVersion record.
 *
 * @var integer $id ID
 * @var integer $entryId Entry ID
 * @var integer $sectionId Section ID
 * @var integer $creatorId Creator ID
 * @var ActiveQueryInterface $locale Locale
 * @var integer $num Num
 * @var string $notes Notes
 * @var array $data Data
 * @var ActiveQueryInterface $entry Entry
 * @var ActiveQueryInterface $section Section
 * @var ActiveQueryInterface $creator Creator

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryVersion extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['num'], 'number', 'min' => 0, 'max' => 65535, 'integerOnly' => true],
			[['locale', 'num', 'data'], 'required'],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%entryversions}}';
	}

	/**
	 * Returns the entry version’s entry.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getEntry()
	{
		return $this->hasOne(Entry::className(), ['id' => 'entryId']);
	}

	/**
	 * Returns the entry version’s section.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSection()
	{
		return $this->hasOne(Section::className(), ['id' => 'sectionId']);
	}

	/**
	 * Returns the entry version’s creator.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getCreator()
	{
		return $this->hasOne(User::className(), ['id' => 'creatorId']);
	}

	/**
	 * Returns the entry version’s locale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'locale']);
	}
}
