<?php
namespace Blocks;

/**
 *
 */
class UserProfileBlockRecord extends BaseBlockRecord
{
	protected $reservedHandleWords = array('id', 'username', 'photo', 'firstName', 'lastName', 'email', 'password', 'encType', 'language', 'emailFormat', 'admin', 'status', 'lastLoginDate', 'invalidLoginCount', 'lastInvalidLoginDate', 'lockoutDate', 'passwordResetRequired', 'lastPasswordChangeDate', 'dateCreated', 'verificationRequired', 'newPassword', 'groups', 'fullName', 'friendlyName', 'isCurrent', 'cooldownEndTime', 'remainingCooldownTime', 'photoUrl');

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'userprofileblocks';
	}
}
