<?php
namespace Blocks;

/**
 *
 */
class UserRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'users';
	}

	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'username'                   => array(AttributeType::String, 'maxLength' => 100, 'required' => true, 'unique' => true),
			'photo'                      => array(AttributeType::String, 'maxLength' => 50),
			'firstName'                  => array(AttributeType::String, 'maxLength' => 100),
			'lastName'                   => array(AttributeType::String, 'maxLength' => 100),
			'email'                      => array(AttributeType::Email, 'required' => true, 'unique' => true),
			'password'                   => array(AttributeType::String, 'maxLength' => 255, 'column' => ColumnType::Char),
			'encType'                    => array(AttributeType::String, 'maxLength' => 10, 'column' => ColumnType::Char),
			'language'                   => array(AttributeType::Language, 'required' => true, 'default' => Blocks::getLanguage()),
			'emailFormat'                => array(AttributeType::Enum, 'values' => array('text', 'html'), 'default' => 'text', 'required' => true),
			'admin'                      => array(AttributeType::Bool),
			'status'                     => array(AttributeType::Enum, 'values' => array('locked', 'suspended', 'pending', 'active', 'archived'), 'default' => 'pending'),
			'lastLoginDate'              => array(AttributeType::DateTime),
			'lastLoginAttemptIPAddress'  => array(AttributeType::String, 'maxLength' => 45),
			'invalidLoginWindowStart'    => array(AttributeType::DateTime),
			'invalidLoginCount'          => array(AttributeType::Number, 'column' => ColumnType::TinyInt, 'unsigned' => true),
			'lastInvalidLoginDate'       => array(AttributeType::DateTime),
			'lockoutDate'                => array(AttributeType::DateTime),
			'verificationCode'           => array(AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char),
			'verificationCodeIssuedDate' => array(AttributeType::DateTime),
			'passwordResetRequired'      => array(AttributeType::Bool),
			'lastPasswordChangeDate'     => array(AttributeType::DateTime),
			'archivedUsername'           => array(AttributeType::String, 'maxLength' => 100),
			'archivedEmail'              => array(AttributeType::Email),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		$relations = array();

		if (Blocks::hasPackage(BlocksPackage::Users))
		{
			$relations['profile'] = array(static::HAS_ONE, 'UserProfileRecord', 'userId');
			$relations['groups']  = array(static::MANY_MANY, 'UserGroupRecord', 'usergroups_users(userId, groupId)');
		}

		$relations['sessions'] = array(static::HAS_MANY, 'SessionRecord', 'userId');

		return $relations;
	}
}
