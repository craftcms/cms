<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\records;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\enums\ColumnType;

/**
 * Class User record.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class User extends BaseRecord
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
			'element'         => array(static::BELONGS_TO, 'Element', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'preferredLocale' => array(static::BELONGS_TO, 'Locale', 'preferredLocale', 'onDelete' => static::SET_NULL, 'onUpdate' => static::CASCADE),
		);

		if (Craft::$app->getEdition() == Craft::Pro)
		{
			$relations['groups']  = array(static::MANY_MANY, 'UserGroup', 'usergroups_users(userId, groupId)');
		}

		$relations['sessions']              = array(static::HAS_MANY, 'Session', 'userId');

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
	 * @inheritDoc BaseRecord::validate()
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
			$this->addError('username', Craft::t('Spaces are not allowed in the username.'));
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

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'username'                   => [AttributeType::String, 'maxLength' => 100, 'required' => true],
			'photo'                      => [AttributeType::String, 'maxLength' => 50],
			'firstName'                  => [AttributeType::String, 'maxLength' => 100],
			'lastName'                   => [AttributeType::String, 'maxLength' => 100],
			'email'                      => [AttributeType::Email, 'required' => true],
			'password'                   => [AttributeType::String, 'maxLength' => 255, 'column' => ColumnType::Char],
			'preferredLocale'            => [AttributeType::Locale],
			'weekStartDay'               => [AttributeType::Number, 'min' => 0, 'max' => 6, 'required' => true, 'default' => '0'],
			'admin'                      => [AttributeType::Bool],
			'client'                     => [AttributeType::Bool],
			'locked'                     => [AttributeType::Bool],
			'suspended'                  => [AttributeType::Bool],
			'pending'                    => [AttributeType::Bool],
			'archived'                   => [AttributeType::Bool],
			'lastLoginDate'              => [AttributeType::DateTime],
			'lastLoginAttemptIPAddress'  => [AttributeType::String, 'maxLength' => 45],
			'invalidLoginWindowStart'    => [AttributeType::DateTime],
			'invalidLoginCount'          => [AttributeType::Number, 'column' => ColumnType::TinyInt, 'unsigned' => true],
			'lastInvalidLoginDate'       => [AttributeType::DateTime],
			'lockoutDate'                => [AttributeType::DateTime],
			'verificationCode'           => [AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char],
			'verificationCodeIssuedDate' => [AttributeType::DateTime],
			'unverifiedEmail'            => [AttributeType::Email],
			'passwordResetRequired'      => [AttributeType::Bool],
			'lastPasswordChangeDate'     => [AttributeType::DateTime],
		];
	}
}
