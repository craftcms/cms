<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use Craft;
use craft\app\db\ActiveRecord;

Craft::$app->requireEdition(Craft::Client);

/**
 * Stores entry drafts.
 *
 * @property integer $id ID
 * @property integer $entryId Entry ID
 * @property integer $sectionId Section ID
 * @property integer $creatorId Creator ID
 * @property ActiveQueryInterface $locale Locale
 * @property string $name Name
 * @property string $notes Notes
 * @property array $data Data
 * @property ActiveQueryInterface $entry Entry
 * @property ActiveQueryInterface $section Section
 * @property ActiveQueryInterface $creator Creator
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
	 */
	public function rules()
	{
		return [
			[['locale'], 'craft\\app\\validators\\Locale'],
			[['locale', 'name', 'data'], 'required'],
		];
	}

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
}
