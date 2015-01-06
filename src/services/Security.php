<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use craft\app\Craft;
use craft\app\errors\Exception;
use craft\app\web\Application;

/**
 * Class Security service.
 *
 * An instance of the Security service is globally accessible in Craft via [[Application::security `craft()->security`]].
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
		$this->_blowFishHashCost = craft()->config->get('blowfishHashCost');
	}

	/**
	 * @return int
	 */
	public function getMinimumPasswordLength()
	{
		return 6;
	}

	/**
	 * Hashes a given password with the blowfish encryption algorithm.
	 *
	 * @param string $string       The string to hash
	 * @param bool   $validateHash If you want to validate the just generated hash. Will throw an exception if
	 *                             validation fails.
	 *
	 * @throws Exception
	 * @return string The hash.
	 */
	public function hashPassword($string, $validateHash = false)
	{
		$hash = \CPasswordHelper::hashPassword($string, $this->_blowFishHashCost);

		if ($validateHash)
		{
			if (!$this->validatePassword($string, $hash))
			{
				throw new Exception(Craft::t('Could not hash the given string.'));
			}
		}

		return $hash;
	}
}
