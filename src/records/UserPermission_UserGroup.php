<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use craft\app\db\ActiveRecord;

/**
 * Class UserPermission_UserGroup record.
 *
 * @property integer        $id           ID
 * @property integer        $permissionId Permission ID
 * @property integer        $groupId      Group ID
 * @property UserPermission $permission   Permission
 * @property UserGroup      $group        Group
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
            [
                ['permissionId'],
                'unique',
                'targetAttribute' => ['permissionId', 'groupId']
            ],
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
     * @return ActiveQueryInterface The relational query object.
     */
    public function getPermission()
    {
        return $this->hasOne(UserPermission::class,
            ['id' => 'permissionId']);
    }

    /**
     * Returns the user permission user group’s group.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGroup()
    {
        return $this->hasOne(UserGroup::class, ['id' => 'groupId']);
    }
}
