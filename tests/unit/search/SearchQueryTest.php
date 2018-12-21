<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\search;

use Codeception\Test\Unit;
use craft\search\SearchQuery;
use craft\search\SearchQueryTerm;

/**
 * Unit tests for SearcTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class SearchQueryTest extends Unit
{
    const DEFAULT_SEARCH_QUERY_TERM_CONFIG = [
        'exclude' => false,
        'exact' => false,
        'subLeft' => false,
        'subRight' => true,
        'attribute' => null,
        'phrase' => null
    ];
    

    public function testOnlyOr()
    {
        $search = new SearchQuery('OR');

        $this->assertSame([], $search->getTokens());
    }

    /**
     * Test the defaults of the SearchQuery class
     */
    public function testDefaultQueryTokens()
    {
        $search = new SearchQuery('search');

        $this->assertSame('search', $search->getQuery());
        $this->assertInstanceOf(SearchQueryTerm::class, $search->getTokens()[0]);

        $searchDefaults = new SearchQueryTerm([
            'exclude' => false,
            'exact' => false,
            'subLeft' => false,
            'subRight' => true,
            'attribute' => null,
            'term' => $search->getQuery(),
            'phrase' => null
        ]);

        $tokens = $search->getTokens()[0];

        $this->ensureIdenticalSearchTermObjects($searchDefaults, $tokens);
    }


    /**
     *
     */
    public function searchQueryData()
    {
        $quotedPhraseConfig = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        // The $searchQueryTerm->term property will not contain the "" double quotes and will have ['phrase'] set to true
        $quotedPhraseConfig['phrase'] = true;
        $quotedPhraseConfig['term'] = 'Hello';

        return [
            ['i said "Hello"', ['Hello' => $quotedPhraseConfig], 3],
            ['i have spaces and lines', null, 5]
        ];
    }

    /**
     * @dataProvider searchQueryData
     * @param $query
     * @param null $configOptions
     */
    public function testSearchQuery($query, $configOptions = null, int $sizeOfArray = null)
    {
        $exploded = explode(' ', $query);

        // Callback that returns the object.
        $createDefaultFromString = function ($string, $config) {
            if (!isset($config['term'])) {
                $config['term'] = $string;
            }

            return new SearchQueryTerm($config);
        };


        $search = new SearchQuery($query);

        // If we have to count the array. Count the array.
        if ($sizeOfArray !== null){
            $this->assertCount($sizeOfArray, $search->getTokens());
        }

        // Loop through the given tokens.
        foreach ($search->getTokens() as $index => $token) {

            // Get wether the data provider gave us custom config options for this term
            $config = $this->getConfigFromOptions($token->term, $configOptions);

            // Setup the token objects for comparison based on the config above and the term of the token
            $whatItShouldBe = $createDefaultFromString($token->term, $config);
            $whatItIs = $token;

            $this->ensureIdenticalSearchTermObjects($whatItShouldBe, $whatItIs);

            // Test that the index of the searchQueryTerm's is in the correct order by comparing to the exploded string.
            $fromExplodedString = $createDefaultFromString($exploded[$index], $config);
            $this->ensureIdenticalSearchTermObjects($fromExplodedString, $whatItIs);
        }
    }

    public function getConfigFromOptions(string $key = null, array $configOptions = null)
    {
        if (!$configOptions) {
            return self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        }

        if (!array_key_exists($key, $configOptions) || !isset($configOptions[$key])) {
            return self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        }

        return $configOptions[$key];
    }

    public function ensureIdenticalSearchTermObjects(SearchQueryTerm $one, SearchQueryTerm $two)
    {
        $this->assertSame([
            $one->exclude, $one->exact, $one->subLeft, $one->subRight,$one->attribute, $one->term, $one->phrase
        ], [$two->exclude, $two->exact, $two->subLeft, $two->subRight, $two->attribute, $two->term, $two->phrase]);
    }
}