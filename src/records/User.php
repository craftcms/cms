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
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class User record.
 *
 * @var integer $id ID
 * @var ActiveQueryInterface $preferredLocale Preferred locale
 * @var string $username Username
 * @var string $photo Photo
 * @var string $firstName First name
 * @var string $lastName Last name
 * @var string $email Email
 * @var string $password Password
 * @var integer $weekStartDay Week start day
 * @var boolean $admin Admin
 * @var boolean $client Client
 * @var boolean $locked Locked
 * @var boolean $suspended Suspended
 * @var boolean $pending Pending
 * @var boolean $archived Archived
 * @var \DateTime $lastLoginDate Last login date
 * @var string $lastLoginAttemptIPAddress Last login attempt ipaddress
 * @var \DateTime $invalidLoginWindowStart Invalid login window start
 * @var integer $invalidLoginCount Invalid login count
 * @var \DateTime $lastInvalidLoginDate Last invalid login date
 * @var \DateTime $lockoutDate Lockout date
 * @var string $verificationCode Verification code
 * @var \DateTime $verificationCodeIssuedDate Verification code issued date
 * @var string $unverifiedEmail Unverified email
 * @var boolean $passwordResetRequired Password reset required
 * @var \DateTime $lastPasswordChangeDate Last password change date
 * @var ActiveQueryInterface $element Element
 * @var ActiveQueryInterface $sessions Sessions

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
			[['preferredLocale'], 'craft\\app\\validators\\Locale'],
			[['weekStartDay'], 'number', 'min' => 0, 'max' => 6, 'integerOnly' => true],
			[['lastLoginDate'], 'craft\\app\\validators\\DateTime'],
			[['invalidLoginWindowStart'], 'craft\\app\\validators\\DateTime'],
			[['invalidLoginCount'], 'number', 'min' => 0, 'max' => 255, 'integerOnly' => true],
			[['lastInvalidLoginDate'], 'craft\\app\\validators\\DateTime'],
			[['lockoutDate'], 'craft\\app\\validators\\DateTime'],
			[['verificationCodeIssuedDate'], 'craft\\app\\validators\\DateTime'],
			[['lastPasswordChangeDate'], 'craft\\app\\validators\\DateTime'],
			[['username', 'email'], 'unique'],
			[['username', 'email', 'weekStartDay'], 'required'],
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
	 * Returns the user’s preferredLocale.
	 *
	 * @return \yii\db\ActiveQueryInterface The relational query object.
	 */
	public function getPreferredLocale()
	{
		return $this->hasOne(Locale::className(), ['id' => 'preferredLocale']);
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
	 * @inheritDoc ActiveRecord::validate()
	 *
	 * @param null $attributes
	 * @param bool $clearErrors
	 *
	 * @return bool|null
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
		$this->locked = false;
		$this->suspended = false;
		$this->archived = false;
	}
}
