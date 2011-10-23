<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_requestType;
	private $_pathSegments = null;

	public function getPathSegments()
	{
		if ($this->_pathSegments == null)
		{
			$this->_pathSegments = array_merge(array_filter(explode('/', $this->getPathInfo())));
		}

		return $this->_pathSegments;
	}

	public function getCMSRequestType()
	{
		if ($this->_requestType == null)
		{
			if (REQUEST_TYPE == RequestType::ControlPanel)
				$this->_requestType = RequestType::ControlPanel;
			else
				$this->_requestType = RequestType::Site;
		}

		return $this->_requestType;
	}
}