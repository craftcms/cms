<?php
namespace Blocks;

/**
 *
 */
class AccountService extends BaseApplicationComponent
{
	private $_currentUser;

	/**
	 * Gets a user by their username or email.
	 *
	 * @param string $usernameOrEmail
	 * @return User
	 */
	public function getUserByUsernameOrEmail($usernameOrEmail)
	{
		return UserRecord::model()->find(array(
			'condition' => 'username=:usernameOrEmail OR email=:usernameOrEmail',
			'params' => array(':usernameOrEmail' => $usernameOrEmail),
		));
	}

	/**
	 * Gets a user by a verification code.
	 *
	 * @param string $code
	 * @return User
	 */
	public function getUserByVerificationCode($code)
	{
		if ($code)
		{
			return UserRecord::model()->findByAttributes(array(
				'verificationCode' => $code,
			));
		}
	}

	/**
	 * Gets the currently logged-in user.
	 *
	 * @return User
	 */
	public function getCurrentUser()
	{
		if (!empty(blx()->user))
		{
			if (!isset($this->_currentUser))
			{
				$userId = blx()->user->getId();
				$this->_currentUser = UserRecord::model()->findById($userId);
			}

			return $this->_currentUser;
		}
	}

	/**
	 * @return string
	 */
	public function getVerifyAccountUrl()
	{
		return 'verify';
	}

	/**
	 * Generates a new verification code for a user.
	 *
	 * @param UserRecord $user
	 * @param bool $save
	 */
	public function generateVerificationCode(UserRecord $user, $save = true)
	{
		$verificationCode = StringHelper::UUID();
		$issuedDate = new DateTime();
		$duration = new \DateInterval('PT'.ConfigHelper::getTimeInSeconds(blx()->config->verificationCodeDuration) .'S');
		$expiryDate = $issuedDate->add($duration);

		$user->verificationCode = $verificationCode;
		$user->verificationCodeIssuedDate = $issuedDate->getTimestamp();
		$user->verificationCodeExpiryDate = $expiryDate->getTimestamp();

		if ($save)
		{
			$user->save();
		}
	}

	/**
	 * Changes a user's password.
	 *
	 * @param UserRecord $user
	 * @param string $newPassword
	 * @param bool $save
	 * @return bool
	 */
	public function changePassword(UserRecord $user, $newPassword, $save = true)
	{
		$hashAndType = blx()->security->hashPassword($newPassword);
		$user->password = $hashAndType['hash'];
		$user->encType = $hashAndType['encType'];
		$user->status = UserAccountStatus::Active;
		$user->lastPasswordChangeDate = DateTimeHelper::currentTime();
		$user->passwordResetRequired = false;

		if (!$save || $user->save())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param UserRecord $user
	 * @return bool
	 */
	public function forgotPassword(UserRecord $user)
	{
		$user = $this->generateVerificationCode($user);
		return blx()->email->sendEmailByKey($user, 'forgot_password');
	}

	/**
	 * Returns the remaining cooldown time for a user.
	 *
	 * @param UserRecord $user
	 * @return int
	 */
	public function getRemainingCooldownTime(UserRecord $user)
	{
		$cooldownEnd = $user->lastLoginFailedDate + ConfigHelper::getTimeInSeconds(blx()->config->failedPasswordCooldown);
		$cooldownRemaining = $cooldownEnd - DateTimeHelper::currentTime();

		if ($cooldownRemaining > 0)
		{
			return $cooldownRemaining;
		}
	}
}
