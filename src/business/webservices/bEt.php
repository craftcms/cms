<?php

/**
 *
 */
class bEt
{
	private $_endpoint;
	private $_timeout;
	private $_maxRedirects;
	private $_streamPath = null;
	private $_package;

	/**
	 * @param     $endPoint
	 * @param int $timeout
	 * @param int $maxRedirects
	 */
	function __construct($endPoint, $timeout = 6, $maxRedirects = 0)
	{
		$this->_endpoint = $endPoint;
		$this->_timeout = $timeout;
		$this->_maxRedirects = $maxRedirects;

		$this->_package = new bEtPackage();
		$this->_package->licenseKeys = Blocks::app()->site->licenseKeys;
		$this->_package->domain = Blocks::app()->request->serverName;
		$this->_package->edition = Blocks::getEdition();
	}

	/**
	 * @param $path
	 */
	public function setStreamPath($path)
	{
		$this->_streamPath = $path;
	}

	/**
	 * @return \bEtPackage
	 */
	public function getPackage()
	{
		return $this->_package;
	}

	/**
	 * @return bool|\bEtPackage
	 */
	public function phoneHome()
	{
		try
		{
			$client = new HttpClient($this->_endpoint, array(
				'timeout'       =>  $this->_timeout,
				'maxredirects'  =>  $this->_maxRedirects
			));

			$client->setRawData(bJson::encode($this->_package), 'json');

			if ($this->_streamPath !== null)
				$client->setStream($this->_streamPath);

			$response = $client->request('POST');

			if ($response->isSuccessful())
			{
				if ($this->_streamPath !== null)
					return true;

				$packageData = bJson::decode($response->getBody());
				$package = new bEtPackage($packageData);

				// we set the license key status on every request
				Blocks::app()->site->setLicenseKeyStatus($package->licenseKeyStatus);

				return $package;
			}
			else
			{
				if ($this->_streamPath == null)
					Blocks::log('Error in calling '.$this->_endpoint.' Response: '.$response->getBody(), 'warning');
				else
					Blocks::log('Error in calling '.$this->_endpoint.'.', 'warning');
			}
		}
		catch(Exception $e)
		{
			Blocks::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), 'error');
		}
	}
}
