<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\errors\Exception;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;
use yii\base\InvalidParamException;

/**
 * Class Security service.
 *
 * An instance of the Security service is globally accessible in Craft via [[Application::security `Craft::$app->getSecurity()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Security extends \yii\base\Security
{
	// Properties
	// =========================================================================

	/**
	 * @var mixed
	 */
	private $_blowFishHashCost;

	// Public Methods
	// =========================================================================

	/**
	 * @return null
	 */
	public function init()
	{
		parent::init();

		$this->_blowFishHashCost = Craft::$app->getConfig()->get('blowfishHashCost');
	}

	/**
	 * @return int
	 */
	public function getMinimumPasswordLength()
	{
		return 6;
	}

	/**
	 * Hashes a given password with the bcrypt blowfish encryption algorithm.
	 *
	 * @param string $string       The string to hash
	 * @param bool   $validateHash If you want to validate the just generated hash. Will throw an exception if
	 *                             validation fails.
	 *
	 * @throws Exception
	 * @return string The hash.
	 */
	public function hashPassword($password, $validateHash = false)
	{
		$hash = $this->generatePasswordHash($password, $this->_blowFishHashCost);

		if ($validateHash)
		{
			if (!$this->validatePassword($password, $hash))
			{
				throw new InvalidParamException(Craft::t('app', 'Could not hash the given string.'));
			}
		}

		return $hash;
	}

	/**
	 * Returns a validtion key unique to this Craft installation. Craft will initially check the 'validationKey'
	 * config setting and return that if one has been explicitly set. If not, Craft will generate a cryptographically
	 * secure, random key and save it in `craft\storage\validation.key` and server that on future requests.
	 *
	 * Note that if this key ever changes, any data that was encrypted with it will not be accessible.
	 *
	 * @throws Exception
	 * @return mixed|string The validation key.
	 */
	public function getValidationKey()
	{
		if ($key = Craft::$app->getConfig()->get('validationKey'))
		{
			return $key;
		}

		$validationKeyPath = Craft::$app->getPath()->getRuntimePath().'/validation.key';

		if (IOHelper::fileExists($validationKeyPath))
		{
			return StringHelper::trim(IOHelper::getFileContents($validationKeyPath));
		}
		else
		{
			if (!IOHelper::isWritable($validationKeyPath))
			{
				throw new Exception(Craft::t('app', 'Tried to write the validation key to {validationKeyPath}, but could not.', ['validationKeyPath' => $validationKeyPath]));
			}

			$key = $this->generateRandomString();

			if (IOHelper::writeToFile($validationKeyPath, $key))
			{
				return $key;
			}

			throw new Exception(Craft::t('app', 'Tried to write the validation key to {validationKeyPath}, but could not.', ['validationKeyPath' => $validationKeyPath]));
		}
	}
}
