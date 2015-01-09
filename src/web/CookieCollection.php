<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;
use Craft;

/**
 * CookieCollection implements a collection class to store cookies. You normally access it via
 * [[HttpRequest::getCookies()]].
 *
 * Since CookieCollection ultimately extends from [[CMap]], it can be used like an associative array as follows:
 *
 * ```php
 * $cookies[$name] = new HttpCookie($name, $value); // sends a cookie
 * $value = $cookies[$name]->value; // reads a cookie value
 * unset($cookies[$name]);  // removes a cookie
 * ```
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CookieCollection extends \CCookieCollection
{
	/**
	 * Returns a array of validated [[HttpCookie]] objects pulled from $_COOKIES.
	 *
	 * Since Craft uses it's own [[HttpCookie]] class, we're overriding this to prevent is from getting duplicate
	 * identity cookies in the case someone has 'defaultCookieDomain' set to something custom.
	 *
	 * @return array The array of validated cookies
	 */
	protected function getCookies()
	{
		$cookies = [];

		if ($this->getRequest()->enableCookieValidation)
		{
			foreach($_COOKIE as $name => $value)
			{
				if (is_string($value) && ($value = Craft::$app->security->validateData($value)) !== false)
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
