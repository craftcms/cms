<?php
namespace Blocks;

/**
 *
 */
class SecurityService extends \CApplicationComponent
{
	private $_iterationCount;

	/**
	 *
	 */
	function __construct()
	{
		parent::init();
		$this->_iterationCount = blx()->config->getItem('phpPass-iterationCount');
	}

	/**
	 * @return int
	 */
	public function getMinimumPasswordLength()
	{
		return 6;
	}

	/**
	 * @param $password
	 * @throws Exception
	 * @return string
	 */
	public function hashPassword($password)
	{
		$passwordHasher = new \PasswordHash($this->_iterationCount, false);
		$hashAndType = $passwordHasher->hashPassword($password);
		$check = $passwordHasher->checkPassword($password, $hashAndType['hash']);

		if (!$check)
		{
			$passwordHasher = new \PasswordHash($this->_iterationCount, false);
			$hashAndType = $passwordHasher->hashPassword($password);
			$check = $passwordHasher->checkPassword($password, $hashAndType['hash']);
		}

		if ($check)
			return $hashAndType;

		throw new Exception(Blocks::t('Could not hash the given password.'));
	}

	/**
	 * @param $password
	 * @param $storedHash
	 * @return bool
	 */
	public function checkPassword($password, $storedHash)
	{
		$passwordHasher = new \PasswordHash($this->_iterationCount, false);
		$check = $passwordHasher->checkPassword($password, $storedHash);

		return $check;
	}

	/**
	 * @param $code
	 * @return mixed
	 */
	public function getUserByVerificationCode($code)
	{
		return User::model()->findByAttributes(array(
			'verification_code' => $code,
		));
	}

	/**
	 * @param $code
	 * @return mixed
	 * @throws Exception
	 */
	public function validateUserVerificationCode($code)
	{
		$user = $this->getUserByVerificationCode($code);

		if (!$user)
		{
			Blocks::log('Unable to find verification code:'.$code);
			throw new Exception(Blocks::t('Unable to validate the verification code.'));
		}

		if (DateTimeHelper::currentTime() > $user->verification_code_expiry_date)
		{
			Blocks::log('Verification: '.$code.' has already expired.');
			throw new Exception(Blocks::t('Unable to validate the verification code.'));
		}

		return $user;
	}
}
