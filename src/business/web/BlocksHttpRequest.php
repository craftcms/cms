<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_requestType;
	private $_pathSegments = null;
	private $_extension = null;
	private $_siteInfo = null;
	private $_blocksUpdateInfo = null;

	public function setBlocksUpdateInfo($blocksUpdateInfo)
	{
		$this->_blocksUpdateInfo = $blocksUpdateInfo;
	}

	public function getBlocksUpdateInfo()
	{
		return $this->_blocksUpdateInfo;
	}

	public function getPathSegments()
	{
		if ($this->_pathSegments == null)
		{
			$this->_pathSegments = array_merge(array_filter(explode('/', $this->getPathInfo())));
		}

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
			if (REQUEST_TYPE == RequestType::ControlPanel)
				$this->_requestType = RequestType::ControlPanel;
			else
				$this->_requestType = RequestType::Site;
		}

		return $this->_requestType;
	}

	public function getSiteInfo()
	{
		if ($this->_siteInfo == null)
		{
			$this->_siteInfo = Blocks::app()->site->getSiteByUrl();
		}

		return $this->_siteInfo;
	}
}
