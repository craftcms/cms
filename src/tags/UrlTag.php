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
		return $this->url('');
	}

	/**
	 * The resource URL prefix
	 *
	 * @param string $path
	 * @param null   $params
	 * @return string
	 */
	public function resource($path = '', $params = null)
	{
		return $this->generateResourceUrl($path, $params);
	}

	/**
	 * The action URL prefix
	 *
	 * @param string $path
	 * @param null   $params
	 * @return array|string
	 */
	public function action($path = '', $params = null)
	{
		return $this->generateActionUrl($path, $params);
	}

	/**
	 * The URL prefix
	 *
	 * @param string $path
	 * @param null   $params
	 * @return array|string
	 */
	public function url($path = '', $params = null)
	{
		return $this->generateUrl($path, $params);
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
