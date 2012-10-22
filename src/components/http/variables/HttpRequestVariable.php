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
	 * Returns all URL segments.
	 *
	 * @return array
	 */
	public function getSegments()
	{
		return blx()->request->getPathSegments();
	}

	/**
	 * Returns a specific URL segment.
	 *
	 * @param int $num
	 * @param string $default
	 * @return string
	 */
	public function getSegment($num, $default = null)
	{
		return blx()->request->getPathSegment($num, $default);
	}

	/**
	 * Returns the last URL segment.
	 *
	 * @return string
	 */
	public function getLastSegment()
	{
		$segments = blx()->request->getPathSegments();

		if ($segments)
		{
			return $segments[count($segments)-1];
		}
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
}
