<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\db\Table;
use yii\db\ActiveQueryInterface;

/**
 * Class User record.
 *
 * @property int $id ID
 * @property int $photoId Photo ID
 * @property bool $active Active
 * @property bool $pending Pending
 * @property bool $locked Locked
 * @property bool $suspended Suspended
 * @property bool $admin Admin
 * @property string|null $username Username
 * @property string|null $fullName
 * @property string|null $firstName First name
 * @property string|null $lastName Last name
 * @property string|null $email Email
 * @property string|null $password Password
 * @property string|null $lastLoginDate Last login date
 * @property string|null $lastLoginAttemptIp Last login attempt IP
 * @property string|null $invalidLoginWindowStart Invalid login window start
 * @property int|null $invalidLoginCount Invalid login count
 * @property string|null $lastInvalidLoginDate Last invalid login date
 * @property string|null $lockoutDate Lockout date
 * @property bool $hasDashboard Whether the user has a dashboard
 * @property string|null $verificationCode Verification code
 * @property string|null $verificationCodeIssuedDate Verification code issued date
 * @property string|null $unverifiedEmail Unverified email
 * @property bool $passwordResetRequired Password reset required
 * @property string|null $lastPasswordChangeDate Last password change date
 * @property Element $element Element
 * @property Session[] $sessions Sessions
 * @property UserGroup[] $groups User groups
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class User extends ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return Table::USERS;
    }

    /**
     * @return ActiveQuery
     */
    public static function find(): ActiveQuery
    {
        return parent::find()
            ->innerJoinWith(['element element'])
            ->where(['element.dateDeleted' => null]);
    }

    /**
     * @return ActiveQuery
     */
    public static function findWithTrashed(): ActiveQuery
    {
        return static::find()->where([]);
    }

    /**
     * @return ActiveQuery
     */
    public static function findTrashed(): ActiveQuery
    {
        return static::find()->where(['not', ['element.dateDeleted' => null]]);
    }

    /**
     * Returns the user’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the user’s sessions.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSessions(): ActiveQueryInterface
    {
        return $this->hasMany(Session::class, ['userId' => 'id']);
    }

    /**
     * Returns the user’s groups.
     *
     * @return ActiveQueryInterface
     */
    public function getGroups(): ActiveQueryInterface
    {
        return $this->hasMany(UserGroup::class, ['id' => 'groupId'])
            ->viaTable(Table::USERGROUPS_USERS, ['userId' => 'id']);
    }
}
