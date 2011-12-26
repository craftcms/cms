<?php

class ET
{
	private $_endpoint;
	private $_timeout;
	private $_maxRedirects;
	//private $_requestType;
	//private $_params;
	//private $_rawData = null;
	//private $_responseType;
	private $_streamPath = null;
	private $_package;

	function __construct($endPoint, $timeout = 6, $maxRedirects = 0)
	{
		$this->_endpoint = $endPoint;
		//$this->_requestType = $requestType;
		$this->_timeout = $timeout;
		$this->_maxRedirects = $maxRedirects;
		//$this->_params = $params;
		//$this->_responseType = $responseType;

		$this->_package = new ETPackage();
		$this->_package->licenseKeys = Blocks::app()->site->getLicenseKeys();
		$this->_package->domain = Blocks::app()->request->getServerName();
		$this->_package->edition = Blocks::getEdition();
	}

	/*public function setRawData($rawData)
	{
		$this->_rawData = $rawData;
	}*/

	public function setStreamPath($path)
	{
		$this->_streamPath = $path;
	}

	public function getPackage()
	{
		return $this->_package;
	}

	public function phoneHome()
	{
		try
		{
			$client = new HttpClient($this->_endpoint, array(
					'timeout'       =>  $this->_timeout,
					'maxredirects'  =>  $this->_maxRedirects
					));

			//if (isset($this->_params) && count($this->_params) > 0)
			//{
			//	$this->_package->data = $this->_params;
			//	if ($this->_requestType == WebRequestType::POST)
			//		$client->setParameterPost($this->_package);
			//	else
			//	{
			//		if ($this->_requestType == WebRequestType::GET)
			//			$client->setParameterGet($this->_package);
			//	}
			//}
			//else
			//{
				//if ($this->_rawData && $this->_requestType == WebRequestType::POST)
				//{
					$client->setRawData(CJSON::encode($this->_package), 'json');
				//}
				//else
				//{
				//	if ($this->_streamPath !== null && $this->_requestType == WebRequestType::POST && $this->_responseType == WebResponseType::Binary)
				//	{
				//		$client->setStream($this->_streamPath);
				//	}
				//}
			//}

			$response = $client->request('POST');
//$test22 =  CJSON::encode($this->_package);
			if ($response->isSuccessful())
			{
				//$responseBody = CJSON::decode($response->getBody());
				//return new BlocksUpdateData($responseBody);

				$test = $response->getBody();

				$test2 = CJSON::decode($test);
				$package = new ETPackage($test2);

				// we set the license key status on every request
				Blocks::app()->site->setLicenseKeyStatus($package->licenseKeyStatus);

				return $package;
			}
			else
			{
				Blocks::log('Error in calling '.$this->_endpoint.' Response: '.$response->getBody(), 'warning');
			}
		}
		catch(Exception $e)
		{
			Blocks::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), 'error');
		}
	}

	public function setLicenseKeyStatus()
	{

	}
}
