<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_requestType;
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

	public function getType()
	{
		if (!isset($this->_requestType))
		{
			if (isset($this->pathSegments[0]) && ($this->pathSegments[0] == Blocks::app()->config('actionTriggerWord')))
			{
				$this->_requestType = RequestType::Action;
			}
			else
			{
				if (REQUEST_TYPE == RequestType::CP)
					$this->_requestType = RequestType::CP;
				else
					$this->_requestType = RequestType::Site;
			}
		}

		return $this->_requestType;
	}
}
