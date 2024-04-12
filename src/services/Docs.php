<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\Json;
use yii\base\Component;

/**
 * Access and display official documentation resources, programmatically.
 */
class Docs extends Component
{
    /**
     * Base URL for developer documentation pages.
     */
    public string $documentationBaseUrl = 'https://craftcms.com/docs/';

    /**
     * Base URL for knowledge base articles.
     */
    public string $kbBaseUrl = 'https://craftcms.com/knowledge-base/';

    /**
     * Base URL for docs "API" requests
     */
    public string $docsApiBaseUrl = 'https://craftcms.com/api/docs/';

    /**
     * Generates a URL to version-specific documentation.
     */
    public function docsUrl(string $path = ''): string
    {
        return $this->documentationBaseUrl . trim($path, '/');
    }

    /**
     * Sends a query to the docs API and returns the decoded response.
     * 
     * @param string $resource API path.
     * @param array $params Query params to send with the request.
     */
    public function makeApiRequest(string $resource, array $params = []): array
    {
        $client = Craft::createGuzzleClient([
            'base_uri' => $this->docsApiBaseUrl,
        ]);

        $response = $client->get($resource, [
            'query' => $params,
        ]);

        return Json::decodeIfJson($response->getBody());
    }
}
