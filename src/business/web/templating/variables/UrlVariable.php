<?php
namespace Blocks;

/**
 * URL functions
 */
class UrlVariable
{
	/**
	 * Returns the current base URL (either for the CP or site, depending on the request mode).
	 * @return string
	 */
	public function base()
	{
		return $this->url('');
	}

	/**
	 * Returns a URL.
	 * @param string $path
	 * @param mixed $params
	 * @return string
	 */
	public function url($path = '', $params = null)
	{
		return UrlHelper::generateUrl($path, $params);
	}

	/**
	 * Returns a resource URL.
	 * @param string $path
	 * @param mixed $params
	 * @return string
	 */
	public function resource($path = '', $params = null)
	{
		return UrlHelper::generateResourceUrl($path, $params);
	}

	/**
	 * Returns an action URL.
	 * @param string $path
	 * @param mixed  $params
	 * @return string
	 */
	public function action($path = '', $params = null)
	{
		return UrlHelper::generateActionUrl($path, $params);
	}

	/**
	 * Returns all URL segments.
	 * @return array
	 */
	public function segments()
	{
		return b()->request->pathSegments;
	}

	/**
	 * Returns a specific URL segment.
	 * @param int    $num Which segment to retrieve
	 * @param string $default
	 * @return bool
	 */
	public function segment($num = null, $default = '')
	{
		return b()->request->getPathSegment($num, $default);
	}

	/**
	 * @returns The request domain.
	 * @return string
	 */
	public function domain()
	{
		return b()->request->serverName;
	}

	/**
	 * @returns A GET variable.
	 * @param string $var
	 * @param string $default
	 * @return bool
	 */
	public function get($var = null, $default = '')
	{
		return b()->request->getQuery($var, $default);
	}
}
