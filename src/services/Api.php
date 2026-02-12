<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\Api as ApiHelper;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\Session;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use yii\base\Component;

/**
 * The API service provides APIs for calling the Craft API (api.craftcms.com).
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getApi()|`Craft::$app->getApi()`]].
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
    public Client $client;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (!isset($this->client)) {
            $this->client = Craft::createGuzzleClient([
                'base_uri' => Craft::$app->baseApiUrl,
                'connect_timeout' => 15,
            ]);
        }
    }

    /**
     * Returns info about the current Craft license.
     *
     * @param string[] $include
     * @return array
     * @throws GuzzleException if the API gave a non-2xx response
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
     * @param string[] $maxVersions The maximum versions that should be allowed
     * @return array
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
     * @throws GuzzleException if the API gave a non-2xx response
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
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        // Close the PHP session in case this takes a while
        Session::close();

        $options = ArrayHelper::merge($options, [
            'headers' => ApiHelper::headers(),
        ]);

        try {
            $response = $this->client->request($method, $uri, $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            throw $e;
        } finally {
            if (isset($response) && $response->getStatusCode() !== 500) {
                ApiHelper::processResponseHeaders($response->getHeaders());
            }
        }

        return $response;
    }
}
