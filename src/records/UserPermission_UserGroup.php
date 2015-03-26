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

Craft::$app->requireEdition(Craft::Pro);

/**
 * Class UserPermission_UserGroup record.
 *
 * @var integer $id ID
 * @var integer $permissionId Permission ID
 * @var integer $groupId Group ID
 * @var ActiveQueryInterface $permission Permission
 * @var ActiveQueryInterface $group Group

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
