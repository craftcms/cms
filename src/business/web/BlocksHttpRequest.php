<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_requestType;
	private $_pathSegments = null;
	private $_extension = null;
	private $_siteInfo = null;
	private $_blocksUpdateInfo = null;

	public function getBlocksUpdateInfo()
	{
		if ($this->_blocksUpdateInfo !== null)
			return $this->_blocksUpdateInfo;

		if (($keys = Blocks::app()->site->getLicenseKeys()) == null || empty($keys))
			$blocksUpdateInfo['blocksLicenseStatus'] = LicenseKeyStatus::MissingKey;
		else
		{
			// delete the cache if we're in dev mode
			if (Blocks::app()->config('devMode'))
				Blocks::app()->fileCache->delete('blocksUpdateInfo');

			$blocksUpdateInfo = Blocks::app()->fileCache->get('blocksUpdateInfo');
			if ($blocksUpdateInfo === false)
			{
				$blocksUpdateInfo = Blocks::app()->site->versionCheck();
				// set cache expiry to 24 hours. 86400 seconds.
				Blocks::app()->fileCache->set('blocksUpdateInfo', $blocksUpdateInfo, 86400);
			}
		}

		if ($blocksUpdateInfo !== null)
			$this->_blocksUpdateInfo = $blocksUpdateInfo;

		return $this->_blocksUpdateInfo;
	}

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
			$this->_siteInfo = Blocks::app()->site->getSiteByUrl();

		return $this->_siteInfo;
	}
}
