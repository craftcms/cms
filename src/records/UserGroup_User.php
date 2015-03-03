<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;
use craft\app\db\ActiveRecord;

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserGroup_User record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserGroup_User extends ActiveRecord
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
		return '{{%usergroups_users}}';
	}

	/**
	 * Returns the user group user’s group.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getGroup()
	{
		return $this->hasOne(UserGroup::className(), ['id' => 'groupId']);
	}

	/**
	 * Returns the user group user’s user.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getUser()
	{
		return $this->hasOne(User::className(), ['id' => 'userId']);
	}

	/**
	 * @inheritDoc ActiveRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['groupId', 'userId'], 'unique' => true],
		];
	}
}
