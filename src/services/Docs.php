<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\Json;
use craft\helpers\Search;
use craft\helpers\StringHelper;
use Illuminate\Support\Collection;
use yii\base\Component;

/**
 * Access and display official documentation resources, programmatically.
 * 
 * URLs for resources can be overridden via application config, for local development or outright replacement.
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
     * Cache key for documentation sitemap/manifest.
     */
    const DOCS_MANIFEST_CACHE_KEY = 'docs-manifest';

    /**
     * “Docset” or namespace for this version of Craft
     */
    const DOCSET_PREFIX = '5.x';

    /**
     * Generates a URL to version-specific documentation.
     */
    public function docsUrl(string $path = ''): string
    {
        return $this->documentationBaseUrl . trim($path, '/');
    }

    /**
     * Loads the latest developer documentation sitemap for local searching.
     */
    public function getDocsManifest(): array
    {
        return Craft::$app->getCache()->getOrSet([self::DOCS_MANIFEST_CACHE_KEY, 'prefix' => self::DOCSET_PREFIX], function() {
            $client = Craft::createGuzzleClient([
                'base_uri' => $this->documentationBaseUrl,
            ]);

            $response = $client->get('sitemap.js');
            $pages = Json::decodeIfJson($response->getBody());

            $candidates = array_filter($pages, function($page) {
                return strpos($page['path'], '/' . self::DOCSET_PREFIX . '/') === 0;
            });

            return $candidates;
        });
    }

    /**
     * Assigns a `score` property to each known documentation page based on the passed search terms, and returns the top N results in descending order.
     * 
     * @param string $terms Search terms to score pages against
     * @param int $maxResults Return only this many results
     * @return Collection Results, in descending rank
     */
    public function searchDocs(string $terms, int $maxResults = 5): Collection
    {
        $pages = collect($this->getDocsManifest());

        return $pages
            ->map(function($page, $i) use ($terms) {
                $page['score'] = $this->_scorePage($terms, $page);

                return $page;
            })
            ->where('score', '>', 0)
            ->sortBy('score', null, true)
            ->slice(0, $maxResults);
    }

    /**
     * Sends a query to the docs API and returns the decoded response.
     * 
     * @param string $resource API path.
     * @param array $params Query params to send with the request.
     * @return array Decoded JSON response object.
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

    /**
     * Scores a search query against a documentation page.
     * 
     * @param string $terms
     * @param array $page
     * @return int
     */
    private function _scorePage(string $terms, array $page): int
    {
        $score = 0;
        $keywords = explode(' ', Search::normalizeKeywords($terms));

        // Title:
        $titleWords = StringHelper::toWords(Search::normalizeKeywords($page['title']));
        $score += 100 * count(array_intersect($titleWords, $keywords));

        foreach ($titleWords as $word) {
            if (StringHelper::containsAny($word, $keywords)) {
                $score += 50;
            }
        }

        // Summary:
        if ($page['summary']) {
            $summaryWords = StringHelper::toWords(Search::normalizeKeywords($page['summary']));
            $score += count(array_intersect($summaryWords, $keywords));

            foreach ($summaryWords as $word) {
                if (StringHelper::containsAny($word, $keywords)) {
                    $score += 1;
                }
            }
        }

        // Keywords:
        if ($page['keywords']) {
            $score += 20 * count(array_intersect($page['keywords'], $keywords));

            foreach ($page['keywords'] as $word) {
                if (StringHelper::containsAny($word, $keywords)) {
                    $score += 10;
                }
            }
        }

        return $score;
    }
}
