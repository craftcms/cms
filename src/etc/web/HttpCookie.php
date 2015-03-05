<?php
namespace Craft;

/**
 * A HttpCookie instance stores a single cookie, including the cookie name, value, domain, path, expire time and
 * whether it should be access over a secure connection or not..
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.web
 * @since     2.2
 */
class HttpCookie extends \CHttpCookie
{
	/**
	 * Creates a new HttpCookie instance.
	 *
	 * @param string $name    The name of this cookie.
	 * @param string $value   The cookie's value.
	 * @param array  $options The configuration array consisting of name-value pairs that are used to configure this cookie.
	 *
	 * @return HttpCookie
	 */
	public function __construct($name, $value, $options = array())
	{
		// Set the default cookie domain. A user can always override it, if they want.
		if (($defaultCookieDomain = craft()->config->get('defaultCookieDomain')) !== '')
		{
			$this->domain = $defaultCookieDomain;
		}

		$this->httpOnly = true;

		$secureCookies = craft()->config->get('useSecureCookies');

		// If it's set to auto and a secure connection or it's set to true, set the secure flag.
		if (($secureCookies === 'auto' && craft()->request->isSecureConnection()) || $secureCookies === true)
		{
			$this->secure = true;
		}

		parent::__construct($name, $value, $options);
	}
}
