<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\enums\AttributeType;

/**
 * Class Entry record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entry extends BaseRecord
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
		return '{{%entries}}';
	}

	/**
	 * Returns the entry’s element.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElement()
	{
		return $this->hasOne(Element::className(), ['id' => 'id']);
	}

	/**
	 * Returns the entry’s section.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSection()
	{
		return $this->hasOne(Section::className(), ['id' => 'sectionId']);
	}

	/**
	 * Returns the entry’s type.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getType()
	{
		return $this->hasOne(EntryType::className(), ['id' => 'typeId']);
	}

	/**
	 * Returns the entry’s author.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getAuthor()
	{
		return $this->hasOne(User::className(), ['id' => 'authorId']);
	}

	/**
	 * Returns the entry’s versions.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getVersions()
	{
		return $this->hasMany(EntryVersion::className(), ['elementId' => 'id']);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['sectionId']],
			['columns' => ['typeId']],
			['columns' => ['postDate']],
			['columns' => ['expiryDate']],
		];
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
			'postDate'   => AttributeType::DateTime,
			'expiryDate' => AttributeType::DateTime,
		];
	}
}
