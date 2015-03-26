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

/**
 * Class Entry record.
 *
 * @var integer $id ID
 * @var integer $sectionId Section ID
 * @var integer $typeId Type ID
 * @var integer $authorId Author ID
 * @var \DateTime $postDate Post date
 * @var \DateTime $expiryDate Expiry date
 * @var ActiveQueryInterface $element Element
 * @var ActiveQueryInterface $section Section
 * @var ActiveQueryInterface $type Type
 * @var ActiveQueryInterface $author Author
 * @var ActiveQueryInterface $versions Versions

 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Entry extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['postDate'], 'craft\\app\\validators\\DateTime'],
			[['expiryDate'], 'craft\\app\\validators\\DateTime'],
		];
	}

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
}
