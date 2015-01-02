<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

/**
 * A HttpCookie instance stores a single cookie, including the cookie name, value, domain, path, expire time and
 * whether it should be access over a secure connection or not..
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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

		parent::__construct($name, $value, $options);
	}
}
