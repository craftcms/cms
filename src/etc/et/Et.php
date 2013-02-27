<?php
namespace Craft;

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
	 * @param     $endpoint
	 * @param int $timeout
	 */
	function __construct($endpoint, $timeout = 6)
	{
		$endpoint .= craft()->config->get('endpointSuffix');

		$this->_endpoint = $endpoint;
		$this->_timeout = $timeout;

		$this->_model = new EtModel();
		$this->_model->url = Craft::getSiteUrl();
		$this->_model->licenseKey = Craft::getLicenseKey();
		$this->_model->requestDomain = craft()->request->getServerName();
		$this->_model->requestIp = craft()->request->getUserHostAddress();
		$this->_model->requestTime = DateTimeHelper::currentTimeStamp();
		$this->_model->requestPort = craft()->request->getPort();
		$this->_model->installedPackages = ArrayHelper::stringToArray(Craft::getPackages());
		$this->_model->localBuild = Craft::getBuild();
		$this->_model->localVersion= Craft::getVersion();

		$this->_options['useragent'] = 'craft-requests/'.\Requests::VERSION;
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
			$data = JsonHelper::encode($this->_model->getAttributes(null, true));
			$response = \Requests::post($this->_endpoint, array(), $data, $this->_options);

			if ($response->success)
			{
				if (isset($this->_options['filename']))
				{
					$fileName = IOHelper::getFileName($this->_options['filename'], false);

					// If the file name is a UUID, we know it was temporarily set and they want to use the name of the file that was on the sending server.
					if (StringHelper::isUUID($fileName))
					{
						$contentDisposition = $response->headers->offsetGet('content-disposition');
						preg_match("/\"(.*)\"/us", $contentDisposition, $matches);
						$fileName = $matches[1];

						IOHelper::rename($this->_options['filename'], IOHelper::getFolderName($this->_options['filename']).$fileName);
					}

					return $fileName;
				}

				$etModel = craft()->et->decodeEtValues($response->body);

				// we set the license key status on every request
				craft()->et->setLicenseKeyStatus($etModel->licenseKeyStatus);

				return $etModel;
			}
			else
			{
				Craft::log('Error in calling '.$this->_endpoint.' Response: '.$response->body, \CLogger::LEVEL_WARNING);
			}
		}
		catch (\Exception $e)
		{
			Craft::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), \CLogger::LEVEL_ERROR);
		}

		return null;
	}
}
