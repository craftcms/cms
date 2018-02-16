<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class UserPermission_UserGroup record.
 *
 * @property int $id ID
 * @property int $permissionId Permission ID
 * @property int $groupId Group ID
 * @property UserPermission $permission Permission
 * @property UserGroup $group Group
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
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%userpermissions_usergroups}}';
    }

    /**
     * Returns the user permission user group’s permission.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getPermission(): ActiveQueryInterface
    {
        return $this->hasOne(UserPermission::class,
            ['id' => 'permissionId']);
    }

    /**
     * Returns the user permission user group’s group.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGroup(): ActiveQueryInterface
    {
        return $this->hasOne(UserGroup::class, ['id' => 'groupId']);
    }
}
