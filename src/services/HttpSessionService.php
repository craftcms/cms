<?php
namespace Craft;

/**
 * Extends CHttpSession to add support for setting the session folder and creating it if it doesn't exist.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
		if ($configVal === true)
		{
			$this->setSavePath(craft()->path->getSessionPath());
		}
		else if (is_string($configVal))
		{
			// If it's set to "auto", let's attempt to check if we're on a distributed cache system
			if ($configVal == 'auto')
			{
				if (mb_strpos($this->getSavePath(), 'tcp://') === false)
				{
					$this->setSavePath(craft()->path->getSessionPath());
				}
			}
			// Otherwise it's a custom save path
			else if (is_string($configVal))
			{
				$this->setSavePath($configVal);
			}
		}

		$this->sessionName = craft()->config->get('phpSessionName');

		parent::init();
	}

	/**
	 * Sets the path to save PHP session files.
	 *
	 * @param string $value The session save path.
	 */
	public function setSavePath($value)
	{
		// Don't make sure $value is a valid directory path, because it might be for a distributed cache system
		session_save_path($value);
	}

	/**
	 * @return boolean Whether the session has started
	 */
	public function isStarted()
	{
		return $this->getIsStarted();
	}

	/**
	 * @return boolean Whether the session has started
	 */
	public function getIsStarted()
	{
		if (function_exists('session_status'))
		{
			return (session_status() != PHP_SESSION_NONE);
		}
		else
		{
			return parent::getIsStarted();
		}
	}
}
