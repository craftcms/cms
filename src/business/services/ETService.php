<?php

class ETService extends CApplicationComponent implements IETService
{
	private $_licenseKeyStatus;

	public function getLicenseKeyStatus()
	{
		if (!isset($this->_licenseKeyStatus))
			$this->_licenseKeyStatus = $this->_getLicenseKeyStatus();

		return $this->_licenseKeyStatus;
		
	}

	public function setLicenseKeyStatus($licenseKeyStatus)
	{
		$this->_licenseKeyStatus = $licenseKeyStatus;
	}

	private function _getLicenseKeyStatus()
	{
		$licenseKeys = Blocks::app()->site->getLicenseKeys();

		if (!$licenseKeys)
			return LicenseKeyStatus::MissingKey;

		$this->ping();
		return $this->_licenseKeyStatus;
		//$licenseKeyStatus = Blocks::app()->fileCache->get('licenseKeyStatus');

		//if (!$licenseKeyStatus)
		//	return $licenseKeyStatus;
	}

	public function ping()
	{
		$et = new ET(ETEndPoints::Ping);

		$response = $et->phoneHome();
	}
}
