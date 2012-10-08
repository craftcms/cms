<?php
namespace Blocks;

	/**
	 * The `$options` parameter takes an associative array with the following
	 * options:
	 *
	 * - `timeout`: How long should we wait for a response? (integer, seconds, default: 10)
	 * - `useragent`: Useragent to send to the server (string, default: php-requests/$version)
	 * - `follow_redirects`: Should we follow 3xx redirects? (boolean, default: true)
	 * - `redirects`: How many times should we redirect before erroring? (integer, default: 10)
	 * - `blocking`: Should we block processing on this request? (boolean, default: true)
	 * - `filename`: File to stream the body to instead. (string|boolean, default: false)
	 * - `auth`: Authentication handler or array of user/password details to use for Basic authentication (RequestsAuth|array|boolean, default: false)
	 * - `idn`: Enable IDN parsing (boolean, default: true)
	 * - `transport`: Custom transport. Either a class name, or a transport object. Defaults to the first working transport from {@see getTransport()} (string|RequestsTransport, default: {@see getTransport()})
	 *
	 */
class Et
{
	private $_endpoint;
	private $_timeout;
	private $_model;
	private $_options = array();

	/**
	 * @return int
	 */
	public function getTimeout()
	{
		return $this->_timeout;
	}

	/**
	 * @param $followRedirects
	 */
	public function setFollowRedirects($followRedirects)
	{
		$this->_options['follow_redirects'] = $followRedirects;
	}

	/**
	 * @param $maxRedirects
	 */
	public function setMaxRedirects($maxRedirects)
	{
		$this->_options['redirects'] = $maxRedirects;
	}

	/**
	 * @param $blocking
	 */
	public function setBlocking($blocking)
	{
		$this->_options['blocking'] = $blocking;
	}

	/**
	 * @param $destinationFileName
	 */
	public function setDestinationFileName($destinationFileName)
	{
		$this->_options['filename'] = $destinationFileName;
	}

	/**
	 * @param     $endPoint
	 * @param int $timeout
	 */
	function __construct($endPoint, $timeout = 6)
	{
		$this->_endpoint = $endPoint;
		$this->_timeout = $timeout;

		//$generalSettings = blx()->systemSettings->getSettings('general');

		$this->_model = new EtModel();
		$this->_model->url = Blocks::getSiteUrl();
		$this->_model->licenseKey = Blocks::getLicenseKey();
		$this->_model->requestDomain = blx()->request->getServerName();
		$this->_model->requestIp = blx()->request->getUserHostAddress();
		$this->_model->requestTime = DateTimeHelper::currentTime();
		$this->_model->requestPort = blx()->request->getPort();

		$this->_options['useragent'] = 'blocks-requests/'.\Requests::VERSION;
		$this->_options['timeout'] = $this->_timeout;
	}

	/**
	 * @return EtModel
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/**
	 * @return bool|EtModel
	 */
	public function phoneHome()
	{
		try
		{
			$data = JsonHelper::encode($this->_model);
			$response = \Requests::post($this->_endpoint, array(), $data, $this->_options);

			if ($response->success)
			{
				if (isset($this->_options['filename']))
				{
					return true;
				}

				$values = JsonHelper::decode($response->body);
				$etModel = blx()->et->decodeEtValues($values);

				// we set the license key status on every request
				blx()->et->setLicenseKeyStatus($etModel->licenseKeyStatus);

				return $etModel;
			}
			else
			{
				Blocks::log('Error in calling '.$this->_endpoint.' Response: '.$response->body, 'warning');
			}
		}
		catch (\Exception $e)
		{
			Blocks::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), 'error');
		}

		return null;
	}
}
