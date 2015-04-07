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
 * Class Tag record.
 *
 * @property integer $id ID
 * @property integer $groupId Group ID
 * @property ActiveQueryInterface $element Element
 * @property ActiveQueryInterface $group Group
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Tag extends ActiveRecord
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
		return '{{%tags}}';
	}

	/**
	 * Returns the tag’s element.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElement()
	{
		return $this->hasOne(Element::className(), ['id' => 'id']);
	}

	/**
	 * Returns the tag’s group.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getGroup()
	{
		return $this->hasOne(TagGroup::className(), ['id' => 'groupId']);
	}
}
