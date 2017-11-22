<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Plugin;
use craft\errors\ApiException;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use yii\base\Component;
use yii\base\Exception;

/**
 * The API service provides APIs for calling the Craft API (api.craftcms.com).
 *
 * An instance of the API service is globally accessible in Craft via [[Application::api `Craft::$app->getApi()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Api extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var Client
     */
    public $client;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->client === null) {
            $this->client = Craft::createGuzzleClient([
                'base_uri' => 'https://api.craftcms.com/v1/'
            ]);
        }
    }

    /**
     * Checks for Craft and plugin updates.
     *
     * @return array
     * @throws ApiException if the API gave a non-2xx response
     * @throws Exception if no one is logged in or there isn't a valid license key
     */
    public function getUpdates(): array
    {
        $request = Craft::$app->getRequest();
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new Exception('A user must be logged in to check for updates.');
        }

        $requestBody = [
            'request' => [
                'ip' => $request->getUserIP(),
                'hostname' => $request->getHostName(),
                'port' => $request->getPort(),
            ],
            'user' => [
                'email' => $user->email,
            ],
            'platform' => $this->platformVersions(),
            'cms' => [
                'version' => Craft::$app->getVersion(),
                'edition' => strtolower(Craft::$app->getEditionName()),
                'licenseKey' => $this->cmsLicenseKey()
            ]
        ];

        if (!empty($pluginInfo = $this->pluginInfo())) {
            $requestBody['plugins'] = $pluginInfo;
        }

        $response = $this->request('POST', 'updates', [
            RequestOptions::BODY => Json::encode($requestBody),
        ]);

        return Json::decode((string)$response->getBody());
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return ResponseInterface
     * @throws ApiException
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            return $this->client->request($method, $uri, $options);
        } catch (RequestException $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array
     */
    protected function platformVersions(): array
    {
        $versions = [
            'php' => PHP_VERSION,
        ];

        $db = Craft::$app->getDb();
        $versions[$db->getDriverName()] = $db->getMasterPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);

        foreach (get_loaded_extensions() as $extension) {
            // Be consistent with Composer extension names (see PlatformRepository::buildPackageName())
            // - `ext-` helps prevent a key conflict if the `pgsql` extension is installed
            // - replace spaces with `-`s for "Zend OPcache"
            $key = 'ext-'.str_replace(' ', '-', $extension);
            $versions[$key] = phpversion($extension);
        }

        return $versions;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function cmsLicenseKey(): string
    {
        $path = Craft::$app->getPath()->getLicenseKeyPath();

        // Check to see if the key exists and it's not a temp one.
        if (!is_file($path)) {
            throw new Exception("No license key found at {$path}.");
        }

        $contents = file_get_contents($path);
        if (empty($contents) || $contents === 'temp') {
            throw new Exception("Invalid license key at {$path}.");
        }

        return trim(preg_replace('/[\r\n]+/', '', $contents));
    }

    /**
     * @return array
     */
    protected function pluginInfo(): array
    {
        $info = [];
        $pluginsService = Craft::$app->getPlugins();
        /** @var Plugin[] $plugins */
        $plugins = $pluginsService->getAllPlugins();

        foreach ($plugins as $plugin) {
            $handle = $plugin->getHandle();
            $info[$handle] = [
                'version' => $plugin->getVersion(),
                'licenseKey' => $pluginsService->getPluginLicenseKey($handle),
            ];
        }

        return $info;
    }
}
