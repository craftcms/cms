<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveRecord;
use craft\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Class UserPermission_User record.
 *
 * @property int $id ID
 * @property int $permissionId Permission ID
 * @property int $userId User ID
 * @property UserPermission $permission Permission
 * @property User $user User
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserPermission_User extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['permissionId'], 'unique', 'targetAttribute' => ['permissionId', 'userId']],
        ];
    }

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::USERPERMISSIONS_USERS;
    }

    /**
     * Returns the user permission user’s permission.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getPermission(): ActiveQueryInterface
    {
        return $this->hasOne(UserPermission::class,
            ['id' => 'permissionId']);
    }

    /**
     * Returns the user permission user’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }
}
