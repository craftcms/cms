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
class Et extends \CApplicationComponent
{
	private $_endpoint;
	private $_timeout;
	private $_package;
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

		$generalSettings = blx()->settings->getSystemSettings('general');

		$this->_package = new EtPackage();
		$this->_package->url = Blocks::getSiteUrl();
		$this->_package->licenseKey = Blocks::getLicenseKey();
		$this->_package->product = '@@@product@@@';
		$this->_package->requestDomain = blx()->request->getServerName();
		$this->_package->requestIp = blx()->request->getUserHostAddress();
		$this->_package->requestTime = DateTimeHelper::currentTime();
		$this->_package->requestPort = blx()->request->getPort();

		$this->_options['useragent'] = 'blocks-requests/'.\Requests::VERSION;
		$this->_options['timeout'] = $this->_timeout;
	}

	/**
	 * @return EtPackage
	 */
	public function getPackage()
	{
		return $this->_package;
	}

	/**
	 * @return bool|EtPackage
	 */
	public function phoneHome()
	{
		try
		{
			$data = Json::encode($this->_package);
			$response = \Requests::post($this->_endpoint, array(), $data, $this->_options);

			if ($response->success)
			{
				if (isset($this->_options['filename']))
					return true;

				$packageData = Json::decode($response->body);
				$package = new EtPackage($packageData);

				// we set the license key status on every request
				blx()->et->setLicenseKeyStatus($site, $package->licenseKeyStatus);

				return $package;
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
