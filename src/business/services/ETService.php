<?php

class ETService extends CApplicationComponent implements IUpdateService
{
	private $_licenseKeyStatus;

	public function getLicenseKeyStatus()
	{
		if (!isset($this->_licenseKeyStatus))
		{
			$this->_licenseKeyStatus = $this->_getLicenseKeyStatus();

			if (!$keys)
			{
				$this->_licenseKeyStatus = LicenseKeyStatus::MissingKey;
			}
			else
			{
				
			}
		}
		
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
