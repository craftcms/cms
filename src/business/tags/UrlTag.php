<?php

class UrlTag extends Tag
{
	private $_segments;

	function __construct()
	{
		$this->_segments = Blocks::app()->request->getPathSegments();
	}

	public function segments()
	{
		$tags = array();

		foreach ($this->_segments as $segment)
		{
			$tags[] = new StringTag($segment);
		}

		return new ArrayTag($tags);
	}

	public function segment($num)
	{
		$index = $num - 1;

		if (isset($this->_segments[$index]))
			return new StringTag($this->_segments[$index]);

		return new StringTag;
	}
}
