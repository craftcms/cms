<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\et;

use Craft;
use craft\app\enums\LicenseKeyStatus;
use craft\app\errors\EtException;
use craft\app\errors\Exception;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\DateTimeHelper;
use craft\app\helpers\IOHelper;
use craft\app\models\Et as EtModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Et
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	private $_destinationFilename;

	// Public Methods
	// =========================================================================

	/**
	 * @param     $endpoint
	 * @param int $timeout
	 * @param int $connectTimeout
	 *
	 * @return Et
	 */
	public function __construct($endpoint, $timeout = 30, $connectTimeout = 2)
	{
		$endpoint .= Craft::$app->getConfig()->get('endpointSuffix');

		$this->_endpoint = $endpoint;
		$this->_timeout = $timeout;
		$this->_connectTimeout = $connectTimeout;

		$this->_model = new EtModel([
			'licenseKey'        => $this->_getLicenseKey(),
			'requestUrl'        => Craft::$app->getRequest()->getHostInfo().Craft::$app->getRequest()->getUrl(),
			'requestIp'         => Craft::$app->getRequest()->getUserIP(),
			'requestTime'       => DateTimeHelper::currentTimeStamp(),
			'requestPort'       => Craft::$app->getRequest()->getPort(),
			'localBuild'        => Craft::$app->build,
			'localVersion'      => Craft::$app->version,
			'localEdition'      => Craft::$app->getEdition(),
			'userEmail'         => Craft::$app->getUser()->getIdentity()->email,
			'track'             => Craft::$app->track,
			'serverInfo'        => array(
				'extensions'    => get_loaded_extensions(),
				'phpVersion'    => PHP_VERSION,
				'mySqlVersion'  => Craft::$app->schemaVersion,
				'proc'          => function_exists('proc_open') ? 1 : 0,
			),
		]);

		$this->_userAgent = 'Craft/'.Craft::$app->version.'.'.Craft::$app->build;
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
	 * @param $destinationFilename
	 *
	 * @return null
	 */
	public function setDestinationFilename($destinationFilename)
	{
		$this->_destinationFilename = $destinationFilename;
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

			if (!Craft::$app->getCache()->get('etConnectFailure'))
			{
				try
				{
					$client = new Client([
						'headers' => [
							'User-Agent'  => $this->_userAgent.' '.Client::getDefaultUserAgent()
						],
						'timeout'         => $this->getTimeout(),
						'connect_timeout' => $this->getConnectTimeout(),
						'allow_redirects' => $this->getAllowRedirects(),
					]);

					// Potentially long-running request, so close session to prevent session blocking on subsequent requests.
					Craft::$app->getSession()->close();

					$response = $client->post($this->_endpoint, ['json' => ArrayHelper::toArray($this->_model)]);

					if ($response->getStatusCode() == 200)
					{
						// Clear the connection failure cached item if it exists.
						if (Craft::$app->getCache()->get('etConnectFailure'))
						{
							Craft::$app->getCache()->delete('etConnectFailure');
						}

						if ($this->_destinationFilename)
						{
							$body = $response->getBody();

							// Write it out to the file
							IOHelper::writeToFile($this->_destinationFilename, $body, true);

							// Close the stream.
							$body->close();

							return IOHelper::getFilename($this->_destinationFilename);
						}

						$responseBody = (string)$response->getBody();
						$etModel = Craft::$app->getEt()->decodeEtModel($responseBody);

						if ($etModel)
						{
							if ($missingLicenseKey && !empty($etModel->licenseKey))
							{
								$this->_setLicenseKey($etModel->licenseKey);
							}

							// Cache the license key status and which edition it has
							Craft::$app->getCache()->set('licenseKeyStatus', $etModel->licenseKeyStatus);
							Craft::$app->getCache()->set('licensedEdition', $etModel->licensedEdition);
							Craft::$app->getCache()->set('editionTestableDomain@'.Craft::$app->getRequest()->getHostName(), $etModel->editionTestableDomain ? 1 : 0);

							if ($etModel->licenseKeyStatus == LicenseKeyStatus::MismatchedDomain)
							{
								Craft::$app->getCache()->set('licensedDomain', $etModel->licensedDomain);
							}

							return $etModel;
						}
					}

					// If we made it here something, somewhere went wrong.
					Craft::warning('Error in calling '.$this->_endpoint.' Response: '.$response->getBody(), __METHOD__);

					if (Craft::$app->getCache()->get('etConnectFailure'))
					{
						// There was an error, but at least we connected.
						Craft::$app->getCache()->delete('etConnectFailure');
					}
				}
				catch (RequestException $e)
				{
					Craft::warning('Error in calling '.$this->_endpoint.' Reason: '.$e->getMessage(), __METHOD__);

					if (Craft::$app->getCache()->get('etConnectFailure'))
					{
						// There was an error, but at least we connected.
						Craft::$app->getCache()->delete('etConnectFailure');
					}
				}
			}
		}
		// Let's log and rethrow any EtExceptions.
		catch (EtException $e)
		{
			Craft::error('Error in '.__METHOD__.'. Message: '.$e->getMessage(), __METHOD__);

			if (Craft::$app->getCache()->get('etConnectFailure'))
			{
				// There was an error, but at least we connected.
				Craft::$app->getCache()->delete('etConnectFailure');
			}

			throw $e;
		}
		catch (\Exception $e)
		{
			Craft::error('Error in '.__METHOD__.'. Message: '.$e->getMessage(), __METHOD__);

			// Cache the failure for 5 minutes so we don't try again.
			Craft::$app->getCache()->set('etConnectFailure', true, 300);
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
		$keyFile = Craft::$app->getPath()->getLicenseKeyPath();

		// Check to see if the key exists and it's not a temp one.
		if (IOHelper::fileExists($keyFile) && IOHelper::getFileContents($keyFile) !== 'temp')
		{
			return trim(preg_replace('/[\r\n]+/', '', IOHelper::getFileContents($keyFile)));
		}

		return null;
	}

	/**
	 * @param $key
	 *
	 * @return bool
	 * @throws Exception|EtException
	 */
	private function _setLicenseKey($key)
	{
		$keyFile = Craft::$app->getPath()->getLicenseKeyPath();

		// Make sure the key file does not exist first, or if it exists it is a temp key file.
		// ET should never overwrite a valid license key.
		if (!IOHelper::fileExists($keyFile) || (IOHelper::fileExists($keyFile) && IOHelper::getFileContents($keyFile) == 'temp'))
		{
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

		throw new Exception(Craft::t('app', 'Cannot overwrite an existing valid license.key file.'));
	}

	/**
	 * @return bool
	 */
	private function _isConfigFolderWritable()
	{
		return IOHelper::isWritable(IOHelper::getFolderName(Craft::$app->getPath()->getLicenseKeyPath()));
	}
}
