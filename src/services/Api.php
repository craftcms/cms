<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Composer\Repository\PlatformRepository;
use Composer\Semver\VersionParser;
use Craft;
use craft\enums\LicenseKeyStatus;
use craft\errors\InvalidLicenseKeyException;
use craft\errors\InvalidPluginException;
use craft\helpers\Api as ApiHelper;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use yii\base\Component;
use yii\base\Exception;

/**
 * The API service provides APIs for calling the Craft API (api.craftcms.com).
 * An instance of the API service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getApi()|`Craft::$app->api`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @internal
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
     * Returns info about the current Craft license.
     *
     * @param string[] $include
     * @return array
     * @throws RequestException if the API gave a non-2xx response
     */
    public function getLicenseInfo(array $include = []): array
    {
        $response = $this->request('GET', 'cms-licenses', [
            'query' => ['include' => implode(',', $include)],
        ]);
        $body = Json::decode((string)$response->getBody());
        return $body['license'];
    }

    /**
     * Checks for Craft and plugin updates.
     *
     * @return array
     * @throws RequestException if the API gave a non-2xx response
     */
    public function getUpdates(): array
    {
        $response = $this->request('GET', 'updates');
        return Json::decode((string)$response->getBody());
    }

    /**
     * Returns all country data.
     *
     * @return array
     * @throws RequestException if the API gave a non-2xx response
     */
    public function getCountries(): array
    {
        $cacheKey = 'countries';
        $cache = Craft::$app->getCache();

        if ($cache->exists($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $response = $this->request('GET', 'countries');
        $countries = Json::decode((string)$response->getBody())['countries'];
        $cache->set($cacheKey, $countries, 60 * 60 * 24 * 7);

        return $countries;
    }

    /**
     * Returns plugin details.
     *
     * @param int $pluginId
     *
     * @return array
     * @throws RequestException if the API gave a non-2xx response
     */
    public function getPluginDetails(int $pluginId): array
    {
        $response = $this->request('GET', 'plugin/' . $pluginId);
        return Json::decode((string)$response->getBody());
    }

    /**
     * Returns plugin changelog.
     *
     * @param int $pluginId
     *
     * @return array
     * @throws RequestException if the API gave a non-2xx response
     */
    public function getPluginChangelog(int $pluginId): array
    {
        $response = $this->request('GET', 'plugin/' . $pluginId . '/changelog');
        return Json::decode((string)$response->getBody());
    }

    /**
     * Order checkout.
     *
     * @param array $data
     *
     * @return array
     * @throws RequestException if the API gave a non-2xx response
     */
    public function checkout(array $data): array
    {
        $response = $this->request('POST', 'payments', [
            'headers' => $this->getPluginStoreHeaders(),
            RequestOptions::BODY => Json::encode($data),
        ]);

        return Json::decode((string)$response->getBody());
    }

    /**
     * Returns a list of package names that Composer should be allowed to update when installing/updating packages.
     *
     * @param array $install Package name/version pairs to be installed
     * @return array
     * @throws RequestException if the API gave a non-2xx response
     * @throws Exception if composer.json can't be located
     * @since 3.0.19
     */
    public function getComposerWhitelist(array $install): array
    {
        $composerService = Craft::$app->getComposer();

        // If there's no composer.lock or we can't decode it, then we're done
        $lockPath = $composerService->getLockPath();
        if ($lockPath === null) {
            return array_keys($install);
        }
        $lockData = Json::decode(file_get_contents($lockPath));
        if (empty($lockData) || empty($lockData['packages'])) {
            return array_keys($install);
        }

        $installed = [];

        // Get the installed package versions
        $hashes = [];
        foreach ($lockData['packages'] as $package) {
            $installed[$package['name']] = $package['version'];

            // Should we be including the hash as well?
            if (strpos($package['version'], 'dev-') === 0) {
                $hash = $package['dist']['reference'] ?? $package['source']['reference'] ?? null;
                if ($hash !== null) {
                    $hashes[$package['name']] = $hash;
                }
            }
        }

        // Check for aliases
        $aliases = [];
        if (!empty($lockData['aliases'])) {
            $versionParser = new VersionParser();
            foreach ($lockData['aliases'] as $alias) {
                // Make sure the package is installed, we haven't already assigned an alias to this package,
                // and the alias is for the same version as what's installed
                if (
                    !isset($aliases[$alias['package']]) &&
                    isset($installed[$alias['package']]) &&
                    $alias['version'] === $versionParser->normalize($installed[$alias['package']])
                ) {
                    $aliases[$alias['package']] = $alias['alias'];
                }
            }
        }

        // Append the hashes and aliases
        foreach ($hashes as $name => $hash) {
            $installed[$name] .= '#' . $hash;
        }

        foreach ($aliases as $name => $alias) {
            $installed[$name] .= ' as ' . $alias;
        }

        $jsonPath = Craft::$app->getComposer()->getJsonPath();
        $composerConfig = Json::decode(file_get_contents($jsonPath));
        $minStability = strtolower($composerConfig['minimum-stability'] ?? 'stable');
        if ($minStability === 'rc') {
            $minStability = 'RC';
        }

        $requestBody = [
            'require' => $composerConfig['require'],
            'installed' => $installed,
            'platform' => ApiHelper::platformVersions(true),
            'install' => $install,
            'minimum-stability' => $minStability,
            'prefer-stable' => (bool)($composerConfig['prefer-stable'] ?? false),
        ];

        $response = $this->request('POST', 'composer-whitelist', [
            RequestOptions::BODY => Json::encode($requestBody),
        ]);

        return Json::decode((string)$response->getBody());
    }

    /**
     * Create a cart.
     *
     * @param array $data
     *
     * @return array
     */
    public function createCart(array $data)
    {
        $response = $this->request('POST', 'carts', [
            'headers' => $this->getPluginStoreHeaders(),
            RequestOptions::BODY => Json::encode($data),
        ]);

        return Json::decode((string)$response->getBody());
    }

    /**
     * Get a cart by its order number.
     *
     * @param string $orderNumber
     *
     * @return array
     */
    public function getCart(string $orderNumber)
    {
        $response = $this->request('GET', 'carts/' . $orderNumber);
        return Json::decode((string)$response->getBody());
    }

    /**
     * Update a cart.
     *
     * @param string $orderNumber
     * @param array $data
     *
     * @return array
     */
    public function updateCart(string $orderNumber, array $data)
    {
        $response = $this->request('POST', 'carts/' . $orderNumber, [
            'headers' => $this->getPluginStoreHeaders(),
            RequestOptions::BODY => Json::encode($data),
        ]);

        return Json::decode((string)$response->getBody());
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws RequestException
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $options = ArrayHelper::merge($options, [
            'headers' => ApiHelper::headers(),
        ]);

        $e = null;

        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (RequestException $e) {
            if (($response = $e->getResponse()) === null || $response->getStatusCode() === 500) {
                throw $e;
            }
        }

        // cache license info from the response
        $cache = Craft::$app->getCache();
        $duration = 86400;
        if ($response->hasHeader('X-Craft-Allow-Trials')) {
            $cache->set('editionTestableDomain@' . Craft::$app->getRequest()->getHostName(), (bool)$response->getHeaderLine('X-Craft-Allow-Trials'), $duration);
        }
        if ($response->hasHeader('X-Craft-License-Status')) {
            $cache->set('licenseKeyStatus', $response->getHeaderLine('X-Craft-License-Status'), $duration);
        }
        if ($response->hasHeader('X-Craft-License-Domain')) {
            $cache->set('licensedDomain', $response->getHeaderLine('X-Craft-License-Domain'), $duration);
        }
        if ($response->hasHeader('X-Craft-License-Edition')) {
            $licensedEdition = $response->getHeaderLine('X-Craft-License-Edition');

            switch ($licensedEdition) {
                case 'solo':
                    $licensedEdition = Craft::Solo;
                    break;
                case 'pro':
                    $licensedEdition = Craft::Pro;
                    break;
                default:
                    Craft::error('Invalid X-Craft-License-Edition header value: ' . $licensedEdition, __METHOD__);
            }

            $cache->set('licensedEdition', $licensedEdition, $duration);
        }

        $pluginLicenseStatuses = [];
        $pluginLicenseEditions = [];
        $pluginsService = Craft::$app->getPlugins();
        foreach ($pluginsService->getAllPluginInfo() as $pluginHandle => $pluginInfo) {
            if ($pluginInfo['isInstalled']) {
                $pluginLicenseStatuses[$pluginHandle] = LicenseKeyStatus::Unknown;
            }
        }
        if ($response->hasHeader('X-Craft-Plugin-License-Statuses')) {
            $pluginLicenseInfo = explode(',', $response->getHeaderLine('X-Craft-Plugin-License-Statuses'));
            foreach ($pluginLicenseInfo as $info) {
                list($pluginHandle, $pluginLicenseStatus) = explode(':', $info);
                $pluginLicenseStatuses[$pluginHandle] = $pluginLicenseStatus;
            }
        }
        if ($response->hasHeader('X-Craft-Plugin-License-Editions')) {
            $pluginLicenseInfo = explode(',', $response->getHeaderLine('X-Craft-Plugin-License-Editions'));
            foreach ($pluginLicenseInfo as $info) {
                list($pluginHandle, $pluginLicenseEdition) = explode(':', $info);
                $pluginLicenseEditions[$pluginHandle] = $pluginLicenseEdition;
            }
        }
        foreach ($pluginLicenseStatuses as $pluginHandle => $pluginLicenseStatus) {
            $pluginLicenseEdition = $pluginLicenseEditions[$pluginHandle] ?? null;
            try {
                $pluginsService->setPluginLicenseKeyStatus($pluginHandle, $pluginLicenseStatus, $pluginLicenseEdition);
            } catch (InvalidPluginException $pluginException) {
            }
        }

        // did we just get a new license key?
        if ($response->hasHeader('X-Craft-License')) {
            $license = $response->getHeaderLine('X-Craft-License');
            $path = Craft::$app->getPath()->getLicenseKeyPath();

            //  just in case there's some race condition where two licenses were requested simultaneously...
            if (App::licenseKey() !== null) {
                $i = 0;
                do {
                    $newPath = "{$path}." . ++$i;
                } while (file_exists($newPath));
                $path = $newPath;
                Craft::warning("A new license key was issued, but we already had one. Writing it to {$path} instead.", __METHOD__);
            }

            try {
                FileHelper::writeToFile($path, chunk_split($license, 50));
            } catch (\ErrorException $err) {
                // log and keep going
                Craft::error("Could not write new license key to {$path}: {$err->getMessage()}\nLicense key: {$license}", __METHOD__);
                Craft::$app->getErrorHandler()->logException($err);
            }
        }

        if ($e !== null) {
            throw $e;
        }

        return $response;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns Plugin Store headers
     *
     * @return array
     */
    private function getPluginStoreHeaders()
    {
        $headers = [];

        $craftIdToken = Craft::$app->getPluginStore()->getToken();

        if ($craftIdToken) {
            $headers['Authorization'] = 'Bearer ' . $craftIdToken->accessToken;
        }

        return $headers;
    }
}
