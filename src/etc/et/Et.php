<?php
namespace Craft;

/**
 * Class Et
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.et
 * @since     1.0
 */
class Et
{
	// Properties
	// =========================================================================

	/**
	 * @var string
	 */
	private $_endpoint;

	/**
	 * @var int
	 */
	private $_timeout;

	/**
	 * @var EtModel
	 */
	private $_model;

	/**
	 * @var bool
	 */
	private $_allowRedirects = true;

	/**
	 * @var string
	 */
	private $_userAgent;

	/**
	 * @var string
	 */
	private $_destinationFileName;

	// Public Methods
	// =========================================================================

	/**
	 * @param     $endpoint
	 * @param int $timeout
	 * @param int $connectTimeout
	 *
	 * @return Et
	 */
	public function __construct($endpoint, $timeout = 30, $connectTimeout = 30)
	{
		$endpoint .= craft()->config->get('elliottUrlSuffix');

		$this->_endpoint = $endpoint;
		$this->_timeout = $timeout;
		$this->_connectTimeout = $connectTimeout;

		// There can be a race condition after an update from older Craft versions where they lose session
		// and another call to elliott is made during cleanup.
		$userEmail = craft()->userSession->getUser() ? craft()->userSession->getUser()->email : '';

		$this->_model = new EtModel(array(
			'licenseKey'        => $this->_getLicenseKey(),
			'pluginLicenseKeys' => $this->_getPluginLicenseKeys(),
			'requestUrl'        => craft()->request->getHostInfo().craft()->request->getUrl(),
			'requestIp'         => craft()->request->getIpAddress(),
			'requestTime'       => DateTimeHelper::currentTimeStamp(),
			'requestPort'       => craft()->request->getPort(),
			'localVersion'      => CRAFT_VERSION,
			'localEdition'      => craft()->getEdition(),
			'userEmail'         => $userEmail,
			'showBeta'          => craft()->config->get('showBetaUpdates'),
			'serverInfo'        => array(
				'extensions'    => get_loaded_extensions(),
				'phpVersion'    => PHP_VERSION,
				'mySqlVersion'  => craft()->db->getServerVersion(),
				'proc'          => function_exists('proc_open') ? 1 : 0,
				'totalLocales'  => count(craft()->i18n->getSiteLocales()),
			),
		));

		$this->_userAgent = 'Craft/'.craft()->getVersion();
	}

	/**
	 * The maximum number of seconds to allow for an entire transfer to take place before timing out.  Set 0 to wait
	 * indefinitely.
	 *
	 * @return int
	 */
	public function getTimeout()
	{
		return $this->_timeout;
	}

	/**
	 * The maximum number of seconds to wait while trying to connect. Set to 0 to wait indefinitely.
	 *
	 * @return int
	 */
	public function getConnectTimeout()
	{
		return $this->_connectTimeout;
	}

	/**
	 * Whether or not to follow redirects on the request.  Defaults to true.
	 *
	 * @param $allowRedirects
	 *
	 * @return null
	 */
	public function setAllowRedirects($allowRedirects)
	{
		$this->_allowRedirects = $allowRedirects;
	}

	/**
	 * @return bool
	 */
	public function getAllowRedirects()
	{
		return $this->_allowRedirects;
	}

	/**
	 * @param $destinationFileName
	 *
	 * @return null
	 */
	public function setDestinationFileName($destinationFileName)
	{
		$this->_destinationFileName = $destinationFileName;
	}

	/**
	 * @return EtModel
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/**
	 * Sets custom data on the EtModel.
	 *
	 * @param $data
	 *
	 * @return null
	 */
	public function setData($data)
	{
		$this->_model->data = $data;
	}

	/**
	 * @param $handle
	 */
	public function setHandle($handle)
	{
		$this->_model->handle = $handle;
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

			// No craft/config/license.key file and we can't write to the config folder. Don't even make the call home.
			if ($missingLicenseKey && !$this->_isConfigFolderWritable())
			{
				throw new EtException('Craft needs to be able to write to your “craft/config” folder and it can’t.', 10001);
			}

			if (!craft()->cache->get('etConnectFailure'))
			{
				$data = JsonHelper::encode($this->_model->getAttributes(null, true));

				$client = new \Guzzle\Http\Client();
				$client->setUserAgent($this->_userAgent, true);

				$options = array(
					'timeout'         => $this->getTimeout(),
					'connect_timeout' => $this->getConnectTimeout(),
					'allow_redirects' => $this->getAllowRedirects(),
				);

				$request = $client->post($this->_endpoint, null, null, $options);
				$request->setBody($data, 'application/json');

				// Potentially long-running request, so close session to prevent session blocking on subsequent requests.
				craft()->session->close();

				$response = $request->send();

				if ($response->isSuccessful())
				{
					// Clear the connection failure cached item if it exists.
					if (craft()->cache->get('etConnectFailure'))
					{
						craft()->cache->delete('etConnectFailure');
					}

					if ($this->_destinationFileName)
					{
						$body = $response->getBody();

						// Make sure we're at the beginning of the stream.
						$body->rewind();

						// Write it out to the file
						IOHelper::writeToFile($this->_destinationFileName, $body->getStream(), true);

						// Close the stream.
						$body->close();

						return IOHelper::getFileName($this->_destinationFileName);
					}

					$etModel = craft()->et->decodeEtModel($response->getBody());

					if ($etModel)
					{
						if ($missingLicenseKey && !empty($etModel->licenseKey))
						{
							$this->_setLicenseKey($etModel->licenseKey);
						}

						// Cache the Craft/plugin license key statuses, and which edition Craft is licensed for
						craft()->cache->set('licenseKeyStatus', $etModel->licenseKeyStatus);
						craft()->cache->set('licensedEdition', $etModel->licensedEdition);
						craft()->cache->set('editionTestableDomain@'.craft()->request->getHostName(), $etModel->editionTestableDomain ? 1 : 0);

						if ($etModel->licenseKeyStatus == LicenseKeyStatus::Mismatched)
						{
							craft()->cache->set('licensedDomain', $etModel->licensedDomain);
						}

						if (is_array($etModel->pluginLicenseKeyStatuses))
						{
							foreach ($etModel->pluginLicenseKeyStatuses as $pluginHandle => $licenseKeyStatus)
							{
								craft()->plugins->setPluginLicenseKeyStatus($pluginHandle, $licenseKeyStatus);
							}
						}

						return $etModel;
					}
					else
					{
						Craft::log('Error in calling '.$this->_endpoint.' Response: '.$response->getBody(), LogLevel::Warning);

						if (craft()->cache->get('etConnectFailure'))
						{
							// There was an error, but at least we connected.
							craft()->cache->delete('etConnectFailure');
						}
					}
				}
				else
				{
					Craft::log('Error in calling '.$this->_endpoint.' Response: '.$response->getBody(), LogLevel::Warning);

					if (craft()->cache->get('etConnectFailure'))
					{
						// There was an error, but at least we connected.
						craft()->cache->delete('etConnectFailure');
					}
				}
			}
		}
		// Let's log and rethrow any EtExceptions.
		catch (EtException $e)
		{
			Craft::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), LogLevel::Error);

			if (craft()->cache->get('etConnectFailure'))
			{
				// There was an error, but at least we connected.
				craft()->cache->delete('etConnectFailure');
			}

			throw $e;
		}
		catch (\Exception $e)
		{
			Craft::log('Error in '.__METHOD__.'. Message: '.$e->getMessage(), LogLevel::Error);

			// Cache the failure for 5 minutes so we don't try again.
			craft()->cache->set('etConnectFailure', true, 300);
		}

		return null;
	}

	// Private Methods
	// =========================================================================

	/**
	 * @return null|string
	 */
	private function _getLicenseKey()
	{
		$licenseKeyPath = craft()->path->getLicenseKeyPath();

		if (($keyFile = IOHelper::fileExists($licenseKeyPath)) !== false)
		{
			$key = trim(preg_replace('/[\r\n]+/', '', IOHelper::getFileContents($keyFile)));
			if (strlen($key) === 250)
			{
				return $key;
			}
		}

		return null;
	}

	/**
	 * @return array
	 */
	private function _getPluginLicenseKeys()
	{
		$pluginLicenseKeys = array();
		$pluginsService = craft()->plugins;

		foreach ($pluginsService->getPlugins() as $plugin)
		{
			$pluginHandle = $plugin->getClassHandle();
			$pluginLicenseKeys[$pluginHandle] = $pluginsService->getPluginLicenseKey($pluginHandle);
		}

		return $pluginLicenseKeys;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 * @throws Exception|EtException
	 */
	private function _setLicenseKey($key)
	{
		// Make sure the key file does not exist first. Et will never overwrite a license key.
		if ($this->_getLicenseKey() === null)
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
