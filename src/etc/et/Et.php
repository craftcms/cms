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
	function __construct($endpoint, $timeout = 30)
	{
		$endpoint .= craft()->config->get('endpointSuffix');

		$this->_endpoint = $endpoint;
		$this->_timeout = $timeout;

		$this->_model = new EtModel(array(
			'licenseKey'        => $this->_getLicenseKey(),
			'requestUrl'        => craft()->request->getHostInfo().craft()->request->getUrl(),
			'requestIp'         => craft()->request->getIpAddress(),
			'requestTime'       => DateTimeHelper::currentTimeStamp(),
			'requestPort'       => craft()->request->getPort(),
			'installedPackages' => Craft::getPackages(),
			'localBuild'        => CRAFT_BUILD,
			'localVersion'      => CRAFT_VERSION,
			'userEmail'         => craft()->userSession->getUser()->email,
			'track'             => CRAFT_TRACK,
		));

		$this->_options['useragent'] = 'craft-requests/'.\Requests::VERSION;
		$this->_options['timeout']   = $this->_timeout;
	}

	/**
	 * @return EtModel
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/**
	 * Sets Custom Data on the EtModel.
	 */
	public function setData($data)
	{
		$this->_model->data = $data;
	}

	/**
	 * @throws EtException|\Exception
	 * @return EtModel|null
	 */
	public function phoneHome()
	{
		try
		{
			$missingLicenseKey = empty($this->_model->licenseKey);

			// No craft/config/license.key file and we can't even write to the config folder.  Don't even make the call home.
			if ($missingLicenseKey && !$this->_isConfigFolderWritable())
			{
				throw new EtException('Craft needs to be able to write to your “craft/config” folder and it can’t.', 10001);
			}

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

				$etModel = craft()->et->decodeEtModel($response->body);

				if ($etModel)
				{
					if ($missingLicenseKey && !empty($etModel->licenseKey))
					{
						$this->_setLicenseKey($etModel->licenseKey);
					}

					// Do some packageTrial timestamp to datetime conversions.
					if (!empty($etModel->packageTrials))
					{
						$packageTrials = $etModel->packageTrials;
						foreach ($etModel->packageTrials as $packageHandle => $expiryTimestamp)
						{
							$expiryDate = DateTime::createFromFormat('U', $expiryTimestamp);
							$currentDate = DateTimeHelper::currentUTCDateTime();

							if ($currentDate > $expiryDate)
							{
								unset($packageTrials[$packageHandle]);
							}
						}

						$etModel->packageTrials = $packageTrials;
					}

					// Cache the license key status and which packages are associated with it
					craft()->fileCache->set('licenseKeyStatus', $etModel->licenseKeyStatus);
					craft()->fileCache->set('licensedPackages', $etModel->licensedPackages);
					craft()->fileCache->set('packageTrials', $etModel->packageTrials);

					if ($etModel->licenseKeyStatus == LicenseKeyStatus::MismatchedDomain)
					{
						craft()->fileCache->set('licensedDomain', $etModel->licensedDomain);
					}

					return $etModel;
				}
				else
				{
					Craft::log('Error in calling '.$this->_endpoint.' Response: '.$response->body, LogLevel::Warning);
				}
			}
			else
			{
				Craft::log('Error in calling '.$this->_endpoint.' Response: '.$response->body, LogLevel::Warning);
			}
		}
		// Let's log and rethrow any EtExceptions.
		catch (EtException $e)
		{
			Craft::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), LogLevel::Error);
			throw $e;
		}
		catch (\Exception $e)
		{
			Craft::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), LogLevel::Error);
		}

		return null;
	}

	/**
	 * @return null|string
	 */
	private function _getLicenseKey()
	{
		$licenseKeyPath = craft()->path->getLicenseKeyPath();

		if (($keyFile = IOHelper::fileExists($licenseKeyPath)) !== false)
		{
			return trim(preg_replace('/[\r\n]+/', '', IOHelper::getFileContents($keyFile)));
		}

		return null;
	}

	/**
	 * @param $key
	 * @return bool
	 * @throws Exception|EtException
	 */
	private function _setLicenseKey($key)
	{
		// Make sure the key file does not exist first. Et will never overwrite a license key.
		if (($keyFile = IOHelper::fileExists(craft()->path->getLicenseKeyPath())) == false)
		{
			$keyFile = craft()->path->getLicenseKeyPath();

			if ($this->_isConfigFolderWritable())
			{
				preg_match_all("/.{50}/", $key, $matches);

				$formattedKey = '';
				foreach ($matches[0] as $segment)
				{
					$formattedKey .= $segment.PHP_EOL;
				}

				return IOHelper::writeToFile($keyFile, $formattedKey);
			}

			throw new EtException('Craft needs to be able to write to your “craft/config” folder and it can’t.', 10001);
		}

		throw new Exception(Craft::t('Cannot overwrite an existing license.key file.'));
	}

	/**
	 * @return bool
	 */
	private function _isConfigFolderWritable()
	{
	 return IOHelper::isWritable(IOHelper::getFolderName(craft()->path->getLicenseKeyPath()));
	}
}
