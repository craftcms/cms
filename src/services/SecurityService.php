<?php
namespace Craft;

/**
 *
 */
class SecurityService extends BaseApplicationComponent
{
	private $_iterationCount;

	/**
	 *
	 */
	function __construct()
	{
		parent::init();
		$this->_iterationCount = craft()->config->get('phpPass-iterationCount');
	}

	/**
	 * @return int
	 */
	public function getMinimumPasswordLength()
	{
		return 6;
	}

	/**
	 * @param $string
	 * @throws Exception
	 * @return string
	 */
	public function hashString($string)
	{
		$hasher = new \PasswordHash($this->_iterationCount, false);
		$hashAndType = $hasher->hashPassword($string);
		$check = $hasher->checkPassword($string, $hashAndType['hash']);

		if (!$check)
		{
			throw new Exception(Craft::t('Could not hash the given string.'));
		}

		return $hashAndType;
	}

	/**
	 * @param $string
	 * @param $storedHash
	 * @return bool
	 */
	public function checkString($string, $storedHash)
	{
		$hasher = new \PasswordHash($this->_iterationCount, false);
		$check = $hasher->checkPassword($string, $storedHash);

		return $check;
	}
}
