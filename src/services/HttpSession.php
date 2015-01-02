<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use craft\app\web\Application;

/**
 * Extends \CHttpSession to add support for setting the session folder and creating it if it doesn't exist.
 *
 * An instance of the HttpSession service is globally accessible in Craft via [[Application::httpSession `craft()->httpSession`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class HttpSession extends \CHttpSession
{
	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application component.
	 *
	 * @return null
	 */
	public function init()
	{
		$cookieParams = array('httponly' => true);

		if (($defaultCookieDomain = craft()->config->get('defaultCookieDomain')) !== '')
		{
			$cookieParams['domain'] = $defaultCookieDomain;
		}

		// Set the PHP session cookie to HTTP only.
		$this->setCookieParams($cookieParams);

		// Check if the config value has actually been set to true/false
		$configVal = craft()->config->get('overridePhpSessionLocation');

		// If it's set to true, override the PHP save session path.
		if (is_bool($configVal) && $configVal === true)
		{
			$this->setSavePath(craft()->path->getSessionPath());
		}
		// Else if it's not false, then it must be 'auto', so let's attempt to check if we're on a distributed cache
		// system
		else if ($configVal !== false)
		{
			if (mb_strpos($this->getSavePath(), 'tcp://') === false)
			{
				$this->setSavePath(craft()->path->getSessionPath());
			}
		}

		parent::init();
	}

	// For consistency!
	/**
	 * @return bool
	 */
	public function isStarted()
	{
		return $this->getIsStarted();
	}
}
