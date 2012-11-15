<?php
namespace Blocks;

/**
 * Request functions
 */
class HttpRequestVariable
{
	/**
	 * Returns whether this is a secure connection.
	 *
	 * @return bool
	 */
	public function isSecure()
	{
		return blx()->request->isSecureConnection();
	}

	/**
	 * Returns the request's URI.
	 *
	 * @return mixed
	 */
	public function getUri()
	{
		return blx()->request->getPath();
	}

	/**
	 * Returns the request's full URL.
	 *
	 * @return mixed
	 */
	public function getUrl()
	{
		$uri = blx()->request->getPath();
		return UrlHelper::getUrl($uri);
	}

	/**
	 * Returns all URI segments.
	 *
	 * @return array
	 */
	public function getSegments()
	{
		return blx()->request->getSegments();
	}

	/**
	 * Returns a specific URI segment.
	 *
	 * @param int $num
	 * @param string $default
	 * @return string
	 */
	public function getSegment($num, $default = null)
	{
		return blx()->request->getSegment($num, $default);
	}

	/**
	 * Returns the first URI segment.
	 *
	 * @return string
	 */
	public function getFirstSegment()
	{
		return blx()->request->getSegment(1);
	}

	/**
	 * Returns the last URL segment.
	 *
	 * @return string
	 */
	public function getLastSegment()
	{
		$segments = blx()->request->getSegments();

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
		return blx()->request->getParam($name, $default);
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
		return blx()->request->getQuery($name, $default);
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
		return blx()->request->getPost($name, $default);
	}

	/**
	 * Returns the server name.
	 *
	 * @return string
	 */
	public function getServerName()
	{
		return blx()->request->getServerName();
	}

	/**
	 * Returns which URL format we're using (PATH_INFO or the query string)
	 *
	 * @return string
	 */
	public function getUrlFormat()
	{
		return blx()->request->getUrlFormat();
	}
}
