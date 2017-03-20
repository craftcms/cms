<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\et;

use Craft;
use craft\base\Plugin;
use craft\enums\LicenseKeyStatus;
use craft\errors\EtException;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\models\Et as EtModel;
use craft\services\Config;
use GuzzleHttp\Exception\RequestException;
use PDO;
use yii\base\Exception;

/**
 * Class Et
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EtTransport
{
    // Properties
    // =========================================================================

    /**
     * @var string|null
     */
    private $_endpoint;

    /**
     * @var EtModel|null
     */
    private $_model;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param string $endpoint
     */
    public function __construct($endpoint)
    {
        $this->_endpoint = $endpoint;

        // There can be a race condition after an update from older Craft versions where they lose session
        // and another call to elliott is made during cleanup.
        $user = Craft::$app->getUser()->getIdentity();
        $userEmail = $user ? $user->email : '';

        $this->_model = new EtModel([
            'licenseKey' => $this->_getLicenseKey(),
            'pluginLicenseKeys' => $this->_getPluginLicenseKeys(),
            'requestUrl' => Craft::$app->getRequest()->getAbsoluteUrl(),
            'requestIp' => Craft::$app->getRequest()->getUserIP(),
            'requestTime' => DateTimeHelper::currentTimeStamp(),
            'requestPort' => Craft::$app->getRequest()->getPort(),
            'localVersion' => Craft::$app->version,
            'localEdition' => Craft::$app->getEdition(),
            'userEmail' => $userEmail,
            'showBeta' => Craft::$app->getConfig()->getGeneral()->showBetaUpdates,
            'serverInfo' => [
                'extensions' => get_loaded_extensions(),
                'phpVersion' => PHP_VERSION,
                'databaseType' => Craft::$app->getConfig()->getDb()->driver,
                'databaseVersion' => Craft::$app->getDb()->pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
                'proc' => function_exists('proc_open') ? 1 : 0,
            ],
        ]);
    }

    /**
     * Sets custom data on the EtModel.
     *
     * @param mixed $data
     *
     * @return void
     */
    public function setData($data)
    {
        $this->_model->data = $data;
    }

    /**
     * Sets the handle ("craft" or a plugin handle) that is the subject for the request.
     *
     * @param string $handle
     *
     * @return void
     */
    public function setHandle(string $handle)
    {
        $this->_model->handle = $handle;
    }

    /**
     * @throws EtException|\Exception
     * @return EtModel|null
     */
    public function phoneHome()
    {
        $cacheService = Craft::$app->getCache();

        try {
            $missingLicenseKey = empty($this->_model->licenseKey);

            // No config/license.key file and we can't write to the config folder. Don't even make the call home.
            if ($missingLicenseKey && !$this->_isConfigFolderWritable()) {
                throw new EtException('Craft needs to be able to write to your config/ folder and it can’t.', 10001);
            }

            if (!Craft::$app->getCache()->get('etConnectFailure')) {
                try {
                    $client = Craft::createGuzzleClient(['timeout' => 120, 'connect_timeout' => 120]);

                    // Potentially long-running request, so close session to prevent session blocking on subsequent requests.
                    Craft::$app->getSession()->close();

                    $response = $client->request('post', $this->_endpoint, [
                        'json' => $this->_model->toArray([
                            // No need to include responseErrors here
                            'licenseKey',
                            'licenseKeyStatus',
                            'licensedEdition',
                            'licensedDomain',
                            'editionTestableDomain',
                            'pluginLicenseKeys',
                            'pluginLicenseKeyStatuses',
                            'data',
                            'requestUrl',
                            'requestIp',
                            'requestTime',
                            'requestPort',
                            'localVersion',
                            'localEdition',
                            'userEmail',
                            'showBeta',
                            'serverInfo',
                            'handle',
                        ])
                    ]);

                    if ($response->getStatusCode() === 200) {
                        // Clear the connection failure cached item if it exists.
                        if ($cacheService->get('etConnectFailure')) {
                            $cacheService->delete('etConnectFailure');
                        }

                        $responseBody = (string)$response->getBody();
                        $etModel = Craft::$app->getEt()->decodeEtModel($responseBody);

                        if ($etModel) {
                            if ($missingLicenseKey && !empty($etModel->licenseKey)) {
                                $this->_setLicenseKey($etModel->licenseKey);
                            }

                            // Cache the Craft/plugin license key statuses, and which edition Craft is licensed for
                            $cacheService->set('licenseKeyStatus', $etModel->licenseKeyStatus);
                            $cacheService->set('licensedEdition', $etModel->licensedEdition);
                            $cacheService->set('editionTestableDomain@'.Craft::$app->getRequest()->getHostName(), $etModel->editionTestableDomain ? 1 : 0);

                            if ($etModel->licenseKeyStatus === LicenseKeyStatus::Mismatched) {
                                $cacheService->set('licensedDomain', $etModel->licensedDomain);
                            }

                            if (is_array($etModel->pluginLicenseKeyStatuses)) {
                                $pluginsService = Craft::$app->getPlugins();

                                foreach ($etModel->pluginLicenseKeyStatuses as $packageName => $licenseKeyStatus) {
                                    if ($plugin = $pluginsService->getPluginByPackageName($packageName)) {
                                        /** @var Plugin $plugin */
                                        $pluginsService->setPluginLicenseKeyStatus($plugin->handle, $licenseKeyStatus);
                                    }
                                }
                            }

                            return $etModel;
                        }
                    }

                    // If we made it here something, somewhere went wrong.
                    Craft::warning('Error in calling '.$this->_endpoint.' Response: '.$response->getBody(), __METHOD__);

                    /** @noinspection NotOptimalIfConditionsInspection */
                    if (Craft::$app->getCache()->get('etConnectFailure')) {
                        // There was an error, but at least we connected.
                        $cacheService->delete('etConnectFailure');
                    }
                } catch (RequestException $e) {
                    Craft::warning('Error in calling '.$this->_endpoint.' Reason: '.$e->getMessage(), __METHOD__);

                    /** @noinspection NotOptimalIfConditionsInspection */
                    if (Craft::$app->getCache()->get('etConnectFailure')) {
                        // There was an error, but at least we connected.
                        $cacheService->delete('etConnectFailure');
                    }
                }
            }
        } // Let's log and rethrow any EtExceptions.
        catch (EtException $e) {
            Craft::error('Error in '.__METHOD__.'. Message: '.$e->getMessage(), __METHOD__);

            if ($cacheService->get('etConnectFailure')) {
                // There was an error, but at least we connected.
                $cacheService->delete('etConnectFailure');
            }

            throw $e;
        } catch (\Exception $e) {
            Craft::error('Error in '.__METHOD__.'. Message: '.$e->getMessage(), __METHOD__);

            // Cache the failure for 5 minutes so we don't try again.
            $cacheService->set('etConnectFailure', true, 300);
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
        if (!is_file($keyFile)) {
            return null;
        }

        $contents = file_get_contents($keyFile);
        if (empty($contents) || $contents === 'temp') {
            return null;
        }

        return trim(preg_replace('/[\r\n]+/', '', $contents));
    }

    /**
     * @return array
     */
    private function _getPluginLicenseKeys(): array
    {
        $pluginLicenseKeys = [];
        $pluginsService = Craft::$app->getPlugins();

        foreach ($pluginsService->getAllPlugins() as $plugin) {
            /** @var Plugin $plugin */
            $pluginLicenseKeys[$plugin->packageName] = $pluginsService->getPluginLicenseKey($plugin->handle);
        }

        return $pluginLicenseKeys;
    }

    /**
     * @param string $key
     *
     * @return bool
     * @throws Exception|EtException
     */
    private function _setLicenseKey(string $key): bool
    {
        // Make sure the key file does not exist first, or if it exists it is a temp key file.
        // ET should never overwrite a valid license key.
        if ($this->_getLicenseKey() !== null) {
            throw new Exception('Cannot overwrite an existing valid license.key file.');
        }

        // Make sure we can write to the file
        if (!$this->_isConfigFolderWritable()) {
            throw new EtException('Craft needs to be able to write to your config/ folder and it can’t.', 10001);
        }

        // Format the license key into lines of 50 chars
        preg_match_all('/.{50}/', $key, $matches);
        $formattedKey = '';
        foreach ($matches[0] as $segment) {
            $formattedKey .= $segment.PHP_EOL;
        }

        FileHelper::writeToFile(Craft::$app->getPath()->getLicenseKeyPath(), $formattedKey);

        return true;
    }

    /**
     * @return bool
     */
    private function _isConfigFolderWritable(): bool
    {
        return FileHelper::isWritable(Craft::$app->getPath()->getConfigPath());
    }
}
