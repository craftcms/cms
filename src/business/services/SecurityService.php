<?php
namespace Blocks;

/**
 *
 */
class SecurityService extends BaseService
{
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

		$et = new Et(EtEndPoints::ValidateKeysByCredentials());
		$et->getPackage()->data = $params;
		$et->phoneHome();
	}

	/**
	 * @param $password
	 * @return string
	 */
	public function hashPassword($password)
	{
		$passwordHasher = new \PasswordHash(8, false);
		$hashAndType = $passwordHasher->hashPassword($password);
		$check = $passwordHasher->checkPassword($password, $hashAndType['hash']);

		if (!$check)
		{
			$passwordHasher = new \PasswordHash(8, true);
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
		$passwordHasher = new \PasswordHash(8, false);
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
		$length = 80;

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
}
