<?php

class UrlTag extends Tag
{
	private $_segments;

	/**
	 * Base
	 */
	public function base()
	{
		$baseUrl = Blocks::app()->urlManager->getBaseUrl();
		return new StringTag($baseUrl);
	}

	/**
	 * Get Segments
	 * @return array The URL segments
	 * @access private
	 */
	private function _getSegments()
	{
		if (!isset($this->_segments))
			$this->_segments = Blocks::app()->request->getPathSegments();

		return $this->_segments;
	}

	/**
	 * Segments
	 */
	public function segments()
	{
		$tags = array();

		foreach ($this->_getSegments() as $segment)
		{
			$tags[] = new StringTag($segment);
		}

		return new ArrayTag($tags);
	}

	/**
	 * Segment
	 * @param int $num Which segment to retrieve
	 */
	public function segment($num)
	{
		$segments = $this->_getSegments();
		$index = $num - 1;

		if (isset($segments[$index]))
			return new StringTag($segments[$index]);

		return new StringTag;
	}
}
