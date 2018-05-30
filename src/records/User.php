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
 * Class User record.
 *
 * @property int $id ID
 * @property string $username Username
 * @property int $photoId Photo ID
 * @property string $firstName First name
 * @property string $lastName Last name
 * @property string $email Email
 * @property string $password Password
 * @property bool $admin Admin
 * @property bool $locked Locked
 * @property bool $suspended Suspended
 * @property bool $pending Pending
 * @property \DateTime $lastLoginDate Last login date
 * @property string $lastLoginAttemptIp Last login attempt IP
 * @property \DateTime $invalidLoginWindowStart Invalid login window start
 * @property int $invalidLoginCount Invalid login count
 * @property \DateTime $lastInvalidLoginDate Last invalid login date
 * @property \DateTime $lockoutDate Lockout date
 * @property bool $hasDashboard Whether the user has a dashboard
 * @property string $verificationCode Verification code
 * @property \DateTime $verificationCodeIssuedDate Verification code issued date
 * @property string $unverifiedEmail Unverified email
 * @property bool $passwordResetRequired Password reset required
 * @property \DateTime $lastPasswordChangeDate Last password change date
 * @property Element $element Element
 * @property Session[] $sessions Sessions
 * @property UserGroup[] $groups User groups
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class User extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%users}}';
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
            ->viaTable('{{%usergroups_users}}', ['userId' => 'id']);
    }
}
