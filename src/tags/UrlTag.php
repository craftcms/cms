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
		return substr(UrlHelper::generateUrl('p'), 0, -1);
	}

	/**
	 * The resource URL prefix
	 */
	public function resource($path = '')
	{
		return UrlHelper::generateResourceUrl($path);
	}

	/**
	 * The action URL prefix
	 */
	public function action($path = '')
	{
		return UrlHelper::generateActionUrl($path);
	}

	/**
	 * Segments
	 * @return array
	 */
	public function segments()
	{
		return Blocks::app()->request->pathSegments;
	}

	/**
	 * Segment
	 * @param int    $num Which segment to retrieve
	 * @param string $default
	 * @return bool
	 */
	public function segment($num = null, $default = '')
	{
		return Blocks::app()->request->getPathSegment($num, $default);
	}

	/**
	 * @return string
	 */
	public function domain()
	{
		return Blocks::app()->request->serverName;
	}

	/**
	 * @param        $var
	 * @param string $default
	 * @return bool
	 */
	public function get($var = null, $default = '')
	{
		return Blocks::app()->request->getQuery($var, $default);
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
