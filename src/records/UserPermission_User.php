<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use Craft;
use craft\app\db\ActiveRecord;

/**
 * Class UserPermission_User record.
 *
 * @property integer        $id           ID
 * @property integer        $permissionId Permission ID
 * @property integer        $userId       User ID
 * @property UserPermission $permission   Permission
 * @property User           $user         User
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserPermission_User extends ActiveRecord
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
                'targetAttribute' => ['permissionId', 'userId']
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
        return '{{%userpermissions_users}}';
    }

    /**
     * Returns the user permission user’s permission.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getPermission()
    {
        return $this->hasOne(UserPermission::className(),
            ['id' => 'permissionId']);
    }

    /**
     * Returns the user permission user’s user.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }
}
