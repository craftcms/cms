<?php
namespace Blocks;

/**
 *
 */
class UrlTag extends Tag
{
	/**
	 * The base URL for the site.
	 * @return string
	 */
	public function base()
	{
		return $this->generateUrl('');
	}

	/**
	 * The resource URL prefix
	 *
	 * @param string $path
	 * @return string
	 */
	public function resource($path = '')
	{
		return $this->generateResourceUrl($path);
	}

	/**
	 * The action URL prefix
	 *
	 * @param string $path
	 * @return array|string
	 */
	public function action($path = '')
	{
		return $this->generateActionUrl($path);
	}

	/**
	 * The URL prefix
	 *
	 * @param string $path
	 * @return array|string
	 */
	public function url($path = '')
	{
		return $this->generateUrl($path);
	}

	/**
	 * Segments
	 * @return array
	 */
	public function segments()
	{
		return b()->request->pathSegments;
	}

	/**
	 * Segment
	 * @param int    $num Which segment to retrieve
	 * @param string $default
	 * @return bool
	 */
	public function segment($num = null, $default = '')
	{
		return b()->request->getPathSegment($num, $default);
	}

	/**
	 * @return string
	 */
	public function domain()
	{
		return b()->request->serverName;
	}

	/**
	 * @param        $var
	 * @param string $default
	 * @return bool
	 */
	public function get($var = null, $default = '')
	{
		return b()->request->getQuery($var, $default);
	}

	/**
	 * @param $path
	 * @param null $params
	 * @return string
	 */
	public function generateResourceUrl($path, $params = null)
	{
		return UrlHelper::generateResourceUrl($path, $params);
	}

	/**
	 * @param $path
	 * @param null $params
	 * @return array|string
	 */
	public function generateActionUrl($path, $params = null)
	{
		return UrlHelper::generateActionUrl($path, $params);
	}

	/**
	 * @param $path
	 * @param null $params
	 * @return array|string
	 */
	public function generateUrl($path, $params = null)
	{
		return UrlHelper::generateUrl($path, $params);
	}
}
