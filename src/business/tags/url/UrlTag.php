<?php

class UrlTag extends Tag
{
	private $_segs = null;

	function __construct()
	{
		$this->_segs = Blocks::app()->request->getPathSegments();
	}

	public function segments()
	{
		return new UrlSegmentsTag($this->_segs);
	}

	public function segment($segNum)
	{
		$segIndex = $segNum - 1;

		if (isset($this->_segs[$segIndex]))
			return new StringTag($this->_segs[$segIndex]);

		return new Tag;
	}
}
