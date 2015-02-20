<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use Craft;

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserPermission_User record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserPermission_User extends BaseRecord
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
		return '{{%userpermissions_users}}';
	}

	/**
	 * Returns the user permission user’s permission.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getPermission()
	{
		return $this->hasOne(UserPermission::className(), ['id' => 'permissionId']);
	}

	/**
	 * Returns the user permission user’s user.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getUser()
	{
		return $this->hasOne(User::className(), ['id' => 'userId']);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return [
			['columns' => ['permissionId', 'userId'], 'unique' => true],
		];
	}
}
