<?php

class UrlTag extends Tag
{
	private $_segs = null;

	function __construct()
	{
		$this->_segs = Blocks::app()->request->getPathSegments();
	}

	public function segs()
	{
		return new UrlSegsTag($this->_segs);
	}

	public function seg($segNum)
	{
		if (isset($segs[$segNum]))
			return new StringTag($segs[$segNum]);

		return null;
	}
}
