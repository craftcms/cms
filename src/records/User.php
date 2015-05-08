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

/**
 * Class User record.
 *
 * @property integer $id ID
 * @property string $username Username
 * @property string $photo Photo
 * @property string $firstName First name
 * @property string $lastName Last name
 * @property string $email Email
 * @property string $password Password
 * @property boolean $admin Admin
 * @property boolean $client Client
 * @property boolean $locked Locked
 * @property boolean $suspended Suspended
 * @property boolean $pending Pending
 * @property boolean $archived Archived
 * @property \DateTime $lastLoginDate Last login date
 * @property string $lastLoginAttemptIPAddress Last login attempt ipaddress
 * @property \DateTime $invalidLoginWindowStart Invalid login window start
 * @property integer $invalidLoginCount Invalid login count
 * @property \DateTime $lastInvalidLoginDate Last invalid login date
 * @property \DateTime $lockoutDate Lockout date
 * @property string $verificationCode Verification code
 * @property \DateTime $verificationCodeIssuedDate Verification code issued date
 * @property string $unverifiedEmail Unverified email
 * @property boolean $passwordResetRequired Password reset required
 * @property \DateTime $lastPasswordChangeDate Last password change date
 * @property ActiveQueryInterface $element Element
 * @property ActiveQueryInterface $sessions Sessions
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
			[['lastLoginDate'], 'craft\\app\\validators\\DateTime'],
			[['invalidLoginWindowStart'], 'craft\\app\\validators\\DateTime'],
			[['invalidLoginCount'], 'number', 'min' => 0, 'max' => 255, 'integerOnly' => true],
			[['lastInvalidLoginDate'], 'craft\\app\\validators\\DateTime'],
			[['lockoutDate'], 'craft\\app\\validators\\DateTime'],
			[['verificationCodeIssuedDate'], 'craft\\app\\validators\\DateTime'],
			[['lastPasswordChangeDate'], 'craft\\app\\validators\\DateTime'],
			[['username', 'email'], 'unique'],
			[['username', 'email'], 'required'],
			[['email', 'unverifiedEmail'], 'email'],
			[['email', 'unverifiedEmail'], 'string', 'min' => 5],
			[['username', 'firstName', 'lastName', 'verificationCode'], 'string', 'max' => 100],
			[['photo'], 'string', 'max' => 50],
			[['email', 'password', 'unverifiedEmail'], 'string', 'max' => 255],
			[['lastLoginAttemptIPAddress'], 'string', 'max' => 45],
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
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getElement()
	{
		return $this->hasOne(Element::className(), ['id' => 'id']);
	}

	/**
	 * Returns the user’s sessions.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getSessions()
	{
		return $this->hasMany(Session::className(), ['userId' => 'id']);
	}

	/**
	 * Returns the user’s groups.
	 *
	 * @return \yii\db\ActiveQueryInterface
	 */
	public function getGroups()
	{
		return $this->hasMany(UserGroup::className(), ['id' => 'groupId'])
			->viaTable('{{%usergroups_users}}', ['userId' => 'id']);
	}


	/**
	 * @inheritdoc
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		// Don't allow whitespace in the username.
		if (preg_match('/\s+/', $this->username))
		{
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
