<?php
namespace Craft;

/**
 * Class UserRecord
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.records
 * @since     1.0
 */
class UserRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'users';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		$relations = array(
			'element'         => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'preferredLocale' => array(static::BELONGS_TO, 'LocaleRecord', 'preferredLocale', 'onDelete' => static::SET_NULL, 'onUpdate' => static::CASCADE),
		);

		if (craft()->getEdition() == Craft::Pro)
		{
			$relations['groups']  = array(static::MANY_MANY, 'UserGroupRecord', 'usergroups_users(userId, groupId)');
		}

		$relations['sessions']              = array(static::HAS_MANY, 'SessionRecord', 'userId');

		return $relations;
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('username'), 'unique' => true),
			array('columns' => array('email'), 'unique' => true),
			array('columns' => array('verificationCode')),
			array('columns' => array('uid')),
		);
	}

	/**
	 * Sets a user's status to active.
	 */
	public function setActive()
	{
		$this->pending = false;
		$this->archived = false;
	}

	/**
	 * @inheritDoc BaseRecord::rules()
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();
		$rules[] = array('unverifiedEmail', 'validateUnverifiedEmail');
		$rules[] = array('username', 'validateUsername');
		$rules[] = array(array('firstName', 'lastName'), 'validateName');

		return $rules;
	}

	/**
	 * Validates the unverified email address.
	 */
	public function validateUnverifiedEmail()
	{
		$value = $this->unverifiedEmail;
		$user = craft()->users->getUserByEmail($value);

		// In the case of saving a new user, these will be the identical until they verify their address
		if ($user && $user->email !== $value)
		{
			$this->addError('email', Craft::t('That email address is already in use. Please choose another.'));
		}
	}

	/**
	 * Validates the username.
	 */
	public function validateUsername()
	{
		// Don't allow whitespace in the username.
		if (preg_match('/\s+/', $this->username))
		{
			$this->addError('username', Craft::t('Spaces are not allowed in the username.'));
		}
	}

	/**
	 * Validates the unverified email address.
	 *
	 * @param $attribute
	 */
	public function validateName($attribute)
	{
		$value = $this->$attribute;

		if (strpos($value, '://') !== false)
		{
			$this->addError($attribute, Craft::t('Invalid value “{value}”.', array(
				'value' => $value,
			)));
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'username'                   => array(AttributeType::String, 'maxLength' => 100, 'required' => true),
			'photo'                      => array(AttributeType::String, 'maxLength' => 100),
			'firstName'                  => array(AttributeType::String, 'maxLength' => 100),
			'lastName'                   => array(AttributeType::String, 'maxLength' => 100),
			'email'                      => array(AttributeType::Email, 'required' => true),
			'password'                   => array(AttributeType::String, 'maxLength' => 255, 'column' => ColumnType::Char),
			'preferredLocale'            => array(AttributeType::Locale),
			'weekStartDay'               => array(AttributeType::Number, 'min' => 0, 'max' => 6, 'required' => true, 'default' => '0'),
			'admin'                      => array(AttributeType::Bool),
			'client'                     => array(AttributeType::Bool),
			'locked'                     => array(AttributeType::Bool),
			'suspended'                  => array(AttributeType::Bool),
			'pending'                    => array(AttributeType::Bool),
			'archived'                   => array(AttributeType::Bool),
			'lastLoginDate'              => array(AttributeType::DateTime),
			'lastLoginAttemptIPAddress'  => array(AttributeType::String, 'maxLength' => 45),
			'invalidLoginWindowStart'    => array(AttributeType::DateTime),
			'invalidLoginCount'          => array(AttributeType::Number, 'column' => ColumnType::TinyInt, 'unsigned' => true),
			'lastInvalidLoginDate'       => array(AttributeType::DateTime),
			'lockoutDate'                => array(AttributeType::DateTime),
			'verificationCode'           => array(AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char),
			'verificationCodeIssuedDate' => array(AttributeType::DateTime),
			'unverifiedEmail'            => array(AttributeType::Email),
			'passwordResetRequired'      => array(AttributeType::Bool),
			'lastPasswordChangeDate'     => array(AttributeType::DateTime),
		);
	}
}
