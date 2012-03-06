<?php
namespace Blocks;

/**
 *
 */
class Et
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

		$this->_package = new EtPackage();
		$this->_package->licenseKeys = b()->sites->licenseKeys;
		$this->_package->domain = b()->request->serverName;
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
	 * @return \EtPackage
	 */
	public function getPackage()
	{
		return $this->_package;
	}

	/**
	 * @return bool|\EtPackage
	 */
	public function phoneHome()
	{
		try
		{
			$client = new \HttpClient($this->_endpoint, array(
				'timeout'       =>  $this->_timeout,
				'maxredirects'  =>  $this->_maxRedirects
			));

			$client->setRawData(Json::encode($this->_package), 'json');

			if ($this->_streamPath !== null)
				$client->setStream($this->_streamPath);

			$response = $client->request('POST');

			if ($response->isSuccessful())
			{
				if ($this->_streamPath !== null)
					return true;

				$packageData = Json::decode($response->getBody());
				$package = new EtPackage($packageData);

				// we set the license key status on every request
				b()->sites->setLicenseKeyStatus($package->licenseKeyStatus);

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

		return null;
	}
}
