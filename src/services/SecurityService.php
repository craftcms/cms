<?php
namespace Blocks;

/**
 *
 */
class SecurityService extends BaseService
{
	private $_iterationCount;
	private $_portableHashes;

	/**
	 *
	 */
	public function __construct()
	{
		parent::init();

		$this->_iterationCount = Blocks::app()->config->getItem('phpPass-iterationCount');
		$this->_portableHashes = Blocks::app()->config->getItem('phpPass-portableHashes');
	}


	/**
	 * @param $userName
	 * @param $password
	 * @param $licenseKeys
	 * @param $edition
	 */
	public function validatePTUserCredentialsAndKey($userName, $password, $licenseKeys, $edition)
	{
		$params = array(
			'userName' => $userName,
			'password' => $password,
			'licenseKeys' => $licenseKeys,
			'edition' => $edition
		);

		$et = new Et(EtEndPoints::ValidateKeysByCredentials);
		$et->getPackage()->data = $params;
		$et->phoneHome();
	}

	/**
	 * @param $password
	 * @return string
	 */
	public function hashPassword($password)
	{
		$passwordHasher = new \PasswordHash($this->_iterationCount, $this->_portableHashes);
		$hashAndType = $passwordHasher->hashPassword($password);
		$check = $passwordHasher->checkPassword($password, $hashAndType['hash']);

		if (!$check)
		{
			$passwordHasher = new \PasswordHash($this->_iterationCount, !$this->_portableHashes);
			$hashAndType = $passwordHasher->hashPassword($password);
			$check = $passwordHasher->checkPassword($password, $hashAndType['hash']);
		}

		if ($check)
			return $hashAndType;

		throw new Exception('Could not hash the given password.');
	}

	/**
	 * @param $password
	 * @param $storedHash
	 * @param $storedEncType
	 * @return bool
	 */
	public function checkPassword($password, $storedHash, $storedEncType)
	{
		$passwordHasher = new \PasswordHash($this->_iterationCount, $this->_portableHashes);
		$check = $passwordHasher->checkPassword($password, $storedHash);

		if (!$check)
		{
			if (($storedEncType == 'blowfish' && CRYPT_BLOWFISH !== 1) || ($storedEncType == 'extdes' && CRYPT_EXT_DES !== 1))
				throw new Exception('This password was encrypted with .'.$storedEncType.', but it appears the server does not support it.  It could have been disabled or was created on a different server.');

			return false;
		}

		return true;
	}

	/**
	 * @param $code
	 * @return mixed
	 */
	public function getUserByAuthCode($code)
	{
		return User::model()->findByAttributes(array(
			'authcode' => $code,
		));
	}

	/**
	 * @param $code
	 * @return mixed
	 * @throws Exception
	 */
	public function validateUserRegistration($code)
	{
		$user = $this->_validateAuthorizationRequest($code);
		return $user !== null;
	}

	/**
	 * @param $code
	 * @return mixed
	 * @throws Exception
	 */
	private function _validateAuthorizationRequest($code)
	{
		$user = $this->getUserByAuthCode($code);

		if ($user == null)
		{
			Blocks::log('Unable to find auth code:'.$code);
			throw new Exception('Unable to validate this authorization code.');
		}

		if (DateTimeHelper::currentTime() > $user->authcode_expire_date)
		{
			Blocks::log('AuthCode: '.$code.' has already expired.');
			throw new Exception('Unable to validate this authorization code.');
		}

		if ($user->password !== null)
		{
			Blocks::log('The user account '.$user->username.' already has a password set.  Ignoring the auth code: '.$code.'.');
			throw new Exception('Unable to validate this authorization code.');
		}
		else
		{
			switch ($user->status)
			{
				case UserAccountStatus::Approved:
				{
					Blocks::log('The user account '.$user->username.' has already been approved.');
					throw new Exception('Unable to validate this authorization code.');
					break;
				}

				case UserAccountStatus::Suspended:
				{
					Blocks::log('The user account '.$user->username.' is in a suspended state and can\'t be verified.');
					throw new Exception('Unable to validate this authorization code.');
					break;
				}

				case UserAccountStatus::PasswordLockout:
				{
					Blocks::log('The user account '.$user->username.' is in a locked state and can\'t be verified.');
					throw new Exception('Unable to validate this authorization code.');
					break;
				}

				case UserAccountStatus::PendingVerification:
				{
					return $user;
				}
			}
		}

		return null;
	}
}
