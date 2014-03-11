<?php
namespace Craft;

/**
 * Request functions
 */
class HttpRequestVariable
{
	/**
	 * Returns whether this is an Ajax request.
	 */
	public function isAjax()
	{
		return craft()->request->isAjaxRequest();
	}

	/**
	 * Returns whether this is a secure connection.
	 *
	 * @return bool
	 */
	public function isSecure()
	{
		return craft()->request->isSecureConnection();
	}

	/**
	 * Returns whether this is a Live Preview request.
	 *
	 * @return bool
	 */
	public function isLivePreview()
	{
		return craft()->request->isLivePreview();
	}

	/**
	 * Returns the script name used to access Craft.
	 *
	 * @return string
	 */
	public function getScriptName()
	{
		return craft()->request->getScriptName();
	}

	/**
	 * Returns the request's URI.
	 *
	 * @return mixed
	 */
	public function getPath()
	{
		return craft()->request->getPath();
	}

	/**
	 * Returns the request's full URL.
	 *
	 * @return mixed
	 */
	public function getUrl()
	{
		$uri = craft()->request->getPath();
		return UrlHelper::getUrl($uri);
	}

	/**
	 * Returns all URI segments.
	 *
	 * @return array
	 */
	public function getSegments()
	{
		return craft()->request->getSegments();
	}

	/**
	 * Returns a specific URI segment, or null if the segment doesn't exist.
	 *
	 * @param int $num
	 * @return string|null
	 */
	public function getSegment($num)
	{
		return craft()->request->getSegment($num);
	}

	/**
	 * Returns the first URI segment.
	 *
	 * @return string
	 */
	public function getFirstSegment()
	{
		return craft()->request->getSegment(1);
	}

	/**
	 * Returns the last URL segment.
	 *
	 * @return string
	 */
	public function getLastSegment()
	{
		$segments = craft()->request->getSegments();

		if ($segments)
		{
			return $segments[count($segments)-1];
		}
	}

	/**
	 * Returns a variable from either the query string or the post data.
	 *
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	public function getParam($name, $default = null)
	{
		return craft()->request->getParam($name, $default);
	}

	/**
	 * Returns a variable from the query string.
	 *
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	public function getQuery($name, $default = null)
	{
		return craft()->request->getQuery($name, $default);
	}

	/**
	 * Returns a value from post data.
	 *
	 * @param string $name
	 * @param string $default
	 * @return mixed
	 */
	public function getPost($name, $default = null)
	{
		return craft()->request->getPost($name, $default);
	}

	/**
	 * Returns a \CHttpCookie if it exists, otherwise, null.
	 *
	 * @param $name
	 * @return \CHttpCookie|null
	 */
	public function getCookie($name)
	{
		return craft()->request->getCookie($name);
	}

	/**
	 * Returns the server name.
	 *
	 * @return string
	 */
	public function getServerName()
	{
		return craft()->request->getServerName();
	}

	/**
	 * Returns which URL format we're using (PATH_INFO or the query string)
	 *
	 * @return string
	 */
	public function getUrlFormat()
	{
		if (craft()->config->usePathInfo())
		{
			return 'pathinfo';
		}
		else
		{
			return 'querystring';
		}
	}

	/**
	 * Returns whether the request is coming from a mobile browser.
	 *
	 * @param bool $detectTablets
	 * @return bool
	 */
	public function isMobileBrowser($detectTablets = false)
	{
		return craft()->request->isMobileBrowser($detectTablets);
	}

	/**
	 * Returns the page number if this is a paginated request.
	 *
	 * @return int
	 */
	public function getPageNum()
	{
		return craft()->request->getPageNum();
	}
}
