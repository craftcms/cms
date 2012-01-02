<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_pathSegments;
	private $_extension;

	public function getPathSegments()
	{
		if (!isset($this->_pathSegments))
		{
			$this->_pathSegments = array_merge(array_filter(explode('/', $this->getPathInfo())));
		}

		return $this->_pathSegments;
	}

	public function getPathExtension()
	{
		if (!isset($this->_extension))
		{
			$ext = pathinfo($this->getPathInfo(), PATHINFO_EXTENSION);
			$this->_extension = strtolower($ext);
		}

		return $this->_extension;
	}
}
