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

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserPermission_UserGroup record.
 *
 * @property integer $id ID
 * @property integer $permissionId Permission ID
 * @property integer $groupId Group ID
 * @property ActiveQueryInterface $permission Permission
 * @property ActiveQueryInterface $group Group
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UserPermission_UserGroup extends ActiveRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['permissionId'], 'unique', 'targetAttribute' => ['permissionId', 'groupId']],
		];
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public static function tableName()
	{
		return '{{%userpermissions_usergroups}}';
	}

	/**
	 * Returns the user permission user group’s permission.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getPermission()
	{
		return $this->hasOne(UserPermission::className(), ['id' => 'permissionId']);
	}

	/**
	 * Returns the user permission user group’s group.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getGroup()
	{
		return $this->hasOne(UserGroup::className(), ['id' => 'groupId']);
	}

}
