<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_requestType;
	private $_pathSegments = null;
	private $_extension = null;

	public function getPathSegments()
	{
		if ($this->_pathSegments == null)
			$this->_pathSegments = array_merge(array_filter(explode('/', $this->getPathInfo())));

		return $this->_pathSegments;
	}

	public function getPathExtension()
	{
		if (($ext = pathinfo($this->getPathInfo(), PATHINFO_EXTENSION)) !== '')
			$this->_extension = strtolower($ext);

		return $this->_extension;
	}

	public function getCMSRequestType()
	{
		if ($this->_requestType == null)
		{
			if (isset($this->pathSegments[0]) && ($this->pathSegments[0] == Blocks::app()->config('actionTriggerWord')))
				$this->_requestType = RequestType::Controller;
			else
			{
				if (REQUEST_TYPE == RequestType::ControlPanel)
					$this->_requestType = RequestType::ControlPanel;
				else
					$this->_requestType = RequestType::Site;
			}
		}

		return $this->_requestType;
	}
}
