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
	 * @return string
	 */
	public function generatePassword()
	{
		$validChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890`~!@#$%^&*()-_=+[]\{}|;:\'",./<>?"';
		$randomString = "";
		$length = 60;

		// count the number of chars in the valid chars string so we know how many choices we have
		$numValidChars = strlen($validChars);

		// repeat the steps until we've created a string of the right length
		for ($i = 0; $i < $length; $i++)
		{
			// pick a random number from 1 up to the number of valid chars
			$randomPick = mt_rand(1, $numValidChars);

			// take the random character out of the string of valid chars
			// subtract 1 from $randomPick because strings are indexed starting at 0, and we started picking at 1
			$randomChar = $validChars[$randomPick - 1];

			// add the randomly-chosen char onto the end of our string
			$randomString .= $randomChar;
		}

		// return our randomly generated hashed password and type.
		return $randomString;
	}

	/**
	 * @param $code
	 * @return mixed
	 */
	public function getAuthCodeByCode($code)
	{
		return AuthCode::model()->findByAttributes(array(
			'code' => $code,
		));
	}

	/**
	 * @param $code
	 * @return mixed
	 * @throws Exception
	 */
	public function validateUserRegistration($code)
	{
		$transaction = Blocks::app()->db->beginTransaction();
		try
		{
			$user = $this->_validateAuthorizationRequest($code);

			if ($user !== null)
			{
				$user->status = UserAccountStatus::Approved;
				$user->save();
			}

			$transaction->commit();
			return $user;
		}
		catch(Exception $e)
		{
			$transaction->rollback();
			throw $e;
		}
	}

	/**
	 * @param $code
	 * @return mixed
	 * @throws Exception
	 */
	private function _validateAuthorizationRequest($code)
	{
		$authCode = Blocks::app()->security->getAuthCodeByCode($code);


		if ($authCode == null)
		{
			Blocks::log('Unable to find auth code:'.$code);
			throw new Exception('Unable to validate this authorization code.');
		}
		else
			$user = $authCode->user;

		if ($authCode->date_activated !== null)
		{
			Blocks::log('AuthCode: '.$authCode->code.' has already been activated.');
			throw new Exception('Unable to validate this authorization code.');
		}

		if (DateTimeHelper::currentTime() > $authCode->expiration_date)
		{
			Blocks::log('AuthCode: '.$authCode->code.' has already expired.');
			throw new Exception('Unable to validate this authorization code.');
		}

		if ($authCode->type == AuthorizationCodeType::Registration)
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
					// validate?
					$authCode->date_activated = DateTimeHelper::currentTime();
					$authCode->save();
					return $user;
				}
			}
		}

		if ($authCode->type == AuthorizationCodeType::ResetPassword || $authCode->type == AuthorizationCodeType::ForgotPassword)
		{
			// verifyauthcode with hashed info?
		}
	}
}
