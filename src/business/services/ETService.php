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

	private function _getLicenseKeyStatus()
	{
		$licenseKeys = Blocks::app()->site->getLicenseKeys();

		if (!$licenseKeys)
			return LicenseKeyStatus::MissingKey;

		$licenseKeyStatus = Blocks::app()->fileCache->get('licenseKeyStatus');

		if (!$licenseKeyStatus)
			return $licenseKeyStatus;

		
	}
}
