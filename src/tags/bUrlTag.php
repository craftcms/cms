<?php

/**
 *
 */
class bUrlTag extends bTag
{
	/**
	 * The base URL for the site.
	 * @return string
	 */
	public function base()
	{
		return Blocks::app()->urlManager->baseUrl;
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
	 * @param int $num Which segment to retrieve
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
	 * @param $var
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
		return bUrlHelper::generateResourceUrl($path, $params);
	}

	/**
	 * @param $path
	 * @param null $params
	 * @return array|string
	 */
	public function generateActionUrl($path, $params = null)
	{
		return bUrlHelper::generateActionUrl($path, $params);
	}

	/**
	 * @param $path
	 * @param null $params
	 * @return array|string
	 */
	public function generateUrl($path, $params = null)
	{
		return bUrlHelper::generateUrl($path, $params);
	}
}
