<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\records;

use yii\db\ActiveQueryInterface;
use Craft;
use craft\app\db\ActiveRecord;
use craft\app\validators\DateTimeValidator;

/**
 * Class User record.
 *
 * @property integer     $id                         ID
 * @property string      $username                   Username
 * @property string      $photo                      Photo
 * @property string      $firstName                  First name
 * @property string      $lastName                   Last name
 * @property string      $email                      Email
 * @property string      $password                   Password
 * @property boolean     $admin                      Admin
 * @property boolean     $client                     Client
 * @property boolean     $locked                     Locked
 * @property boolean     $suspended                  Suspended
 * @property boolean     $pending                    Pending
 * @property boolean     $archived                   Archived
 * @property \DateTime   $lastLoginDate              Last login date
 * @property string      $lastLoginAttemptIp         Last login attempt IP
 * @property \DateTime   $invalidLoginWindowStart    Invalid login window start
 * @property integer     $invalidLoginCount          Invalid login count
 * @property \DateTime   $lastInvalidLoginDate       Last invalid login date
 * @property \DateTime   $lockoutDate                Lockout date
 * @property string      $verificationCode           Verification code
 * @property \DateTime   $verificationCodeIssuedDate Verification code issued date
 * @property string      $unverifiedEmail            Unverified email
 * @property boolean     $passwordResetRequired      Password reset required
 * @property \DateTime   $lastPasswordChangeDate     Last password change date
 * @property Element     $element                    Element
 * @property Session[]   $sessions                   Sessions
 * @property UserGroup[] $groups                     User groups
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class User extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lastLoginDate', 'invalidLoginWindowStart', 'lastInvalidLoginDate', 'lockoutDate', 'lastPasswordChangeDate', 'verificationCodeIssuedDate'], DateTimeValidator::class],
            [
                ['invalidLoginCount'],
                'number',
                'min' => 0,
                'max' => 255,
                'integerOnly' => true
            ],
            [['username', 'email'], 'unique'],
            [['username', 'email'], 'required'],
            [['email', 'unverifiedEmail'], 'email'],
            [['email', 'unverifiedEmail'], 'string', 'min' => 5],
            [
                ['username', 'firstName', 'lastName', 'verificationCode'],
                'string',
                'max' => 100
            ],
            [['photoId'], 'number'],
            [['email', 'password', 'unverifiedEmail'], 'string', 'max' => 255],
            [['lastLoginAttemptIp'], 'string', 'max' => 45],
        ];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName()
    {
        return '{{%users}}';
    }

    /**
     * Returns the user’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement()
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    /**
     * Returns the user’s sessions.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSessions()
    {
        return $this->hasMany(Session::class, ['userId' => 'id']);
    }

    /**
     * Returns the user’s groups.
     *
     * @return ActiveQueryInterface
     */
    public function getGroups()
    {
        return $this->hasMany(UserGroup::class, ['id' => 'groupId'])
            ->viaTable('{{%usergroups_users}}', ['userId' => 'id']);
    }


    /**
     * @inheritdoc
     */
    public function validate($attributes = null, $clearErrors = true)
    {
        // Don't allow whitespace in the username.
        if (preg_match('/\s+/', $this->username)) {
            $this->addError('username', Craft::t('app', 'Spaces are not allowed in the username.'));
        }

        return parent::validate($attributes, false);
    }

    /**
     * Sets a user's status to active.
     */
    public function setActive()
    {
        $this->pending = false;
        $this->archived = false;
    }
}
