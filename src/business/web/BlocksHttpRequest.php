<?php

class BlocksHttpRequest extends CHttpRequest
{
	private $_requestType;
	private $_pathSegments = null;
	private $_extension = null;
	private $_siteInfo = null;
	private $_blocksUpdateInfo;

	public function getBlocksUpdateInfo($fetch = false)
	{
		if (!isset($this->_blocksUpdateInfo) || ($this->_blocksUpdateInfo === false && $fetch))
		{
			if (($keys = Blocks::app()->site->getLicenseKeys()) == null || empty($keys))
			{
				// no license key
				$blocksUpdateInfo['blocksLicenseStatus'] = LicenseKeyStatus::MissingKey;
			}
			else
			{
				// get the update info from the cache if it's there
				$blocksUpdateInfo = Blocks::app()->fileCache->get('blocksUpdateInfo');

				// if it wasn't cached, should we fetch it?
				if ($blocksUpdateInfo === false && $fetch)
				{
					$blocksUpdateInfo = Blocks::app()->site->versionCheck();

					// cache it and set it to expire in 24 hours (86400 seconds) or 60 seconds if dev mode
					$expire = Blocks::app()->config('devMode') ? 60 : 86400;
					Blocks::app()->fileCache->set('blocksUpdateInfo', $blocksUpdateInfo, $expire);
				}
			}

			$this->_blocksUpdateInfo = $blocksUpdateInfo;
		}

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
