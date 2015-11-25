<?php
namespace Craft;

/**
 * CookieCollection implements a collection class to store cookies. You normally access it via
 * {@link HttpRequest::getCookies()}.
 *
 * Since CookieCollection ultimately extends from {@link CMap}, it can be used like an associative array as follows:
 *
 * ```php
 * $cookies[$name] = new HttpCookie($name, $value); // sends a cookie
 * $value = $cookies[$name]->value; // reads a cookie value
 * unset($cookies[$name]);  // removes a cookie
 * ```
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.web
 * @since     2.2
 */
class CookieCollection extends \CCookieCollection
{
	/**
	 * Returns a array of validated {@link HttpCookie} objects pulled from $_COOKIES.
	 *
	 * Since Craft uses it's own {@link HttpCookie} class, we're overriding this to prevent is from getting duplicate
	 * identity cookies in the case someone has 'defaultCookieDomain' set to something custom.
	 *
	 * @return array The array of validated cookies
	 */
	protected function getCookies()
	{
		$cookies = array();

		if ($this->getRequest()->enableCookieValidation)
		{
			foreach($_COOKIE as $name => $value)
			{
				if (is_string($value) && ($value=craft()->security->validateData($value)) !== false)
				{
					$cookies[$name] = new HttpCookie($name, @unserialize($value));
				}
			}
		}
		else
		{
			foreach($_COOKIE as $name => $value)
			{
				$cookies[$name] = new HttpCookie($name, $value);
			}
		}

		return $cookies;
	}
}
