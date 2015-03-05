<?php
namespace Craft;

/**
 * Extends CHttpSession to add support for setting the session folder and creating it if it doesn't exist.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class HttpSessionService extends \CHttpSession
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

		$secureCookies = craft()->config->get('useSecureCookies');

		// If it's set to auto and a secure connection or it's set to true, set the secure flag.
		if (($secureCookies === 'auto' && craft()->request->isSecureConnection()) || $secureCookies === true)
		{
			$cookieParams['secure'] = true;
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
