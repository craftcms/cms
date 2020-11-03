<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Composer\Semver\VersionParser;
use Craft;
use craft\helpers\Api as ApiHelper;
use craft\helpers\ArrayHelper;
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
    /**
     * @var Client
     */
    public $client;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->client === null) {
            $this->client = Craft::createGuzzleClient([
                'base_uri' => Craft::$app->baseApiUrl,
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
     * @param string[] The maximum versions that should be allowed
     * @return array
     * @throws RequestException if the API gave a non-2xx response
     */
    public function getUpdates(array $maxVersions = []): array
    {
        $options = [];
        if ($maxVersions) {
            $maxVersionsStr = [];
            foreach ($maxVersions as $name => $version) {
                $maxVersionsStr[] = "$name:$version";
            }
            $options[RequestOptions::QUERY] = [
                'maxVersions' => implode(',', $maxVersionsStr),
            ];
        }

        $response = $this->request('GET', 'updates', $options);
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

        ApiHelper::processResponseHeaders($response->getHeaders());

        if ($e !== null) {
            throw $e;
        }

        return $response;
    }
}
