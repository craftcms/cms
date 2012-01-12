<?php

/**
 *
 */
class UrlTag extends Tag
{
	private $_segments;

	/**
	 * The base URL for the site.
	 * @return string
	 */
	public function base()
	{
		return Blocks::app()->urlManager->baseUrl;
	}

	/**
	 * Get Segments
	 * @access private
	 * @return array The URL segments
	 */
	private function _getSegments()
	{
		if (!isset($this->_segments))
			$this->_segments = Blocks::app()->request->pathSegments;

		return $this->_segments;
	}

	/**
	 * Segments
	 * @return array
	 */
	public function segments()
	{
		return $this->_getSegments();
	}

	/**
	 * Segment
	 * @param int $num Which segment to retrieve
	 * @return bool
	 */
	public function segment($num)
	{
		$segments = $this->_getSegments();
		$index = $num - 1;

		if (isset($segments[$index]))
			return $segments[$index];

		return false;
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
	public function get($var)
	{
		return isset($_GET[$var]) ? $_GET[$var] : false;
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
