<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\search;

use Codeception\Test\Unit;
use craft\search\SearchQuery;
use craft\search\SearchQueryTerm;
use craft\search\SearchQueryTermGroup;

/**
 * Unit tests for SearchTest
 *
 * Searching and some of the commands run in this test are documented here:
 * https://docs.craftcms.com/v3/searching.html#supported-syntaxes
 *
 * @todo There are MySQL and PostgreSQL specific search tests that need to be performed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SearchQueryTest extends Unit
{
    // Constants
    // =========================================================================

    const DEFAULT_SEARCH_QUERY_TERM_CONFIG = [
        'exclude' => false,
        'exact' => false,
        'subLeft' => false,
        'subRight' => true,
        'attribute' => null,
        'phrase' => null
    ];

    // Public Methods
    // =========================================================================

    /**
     * @param $token
     * @param $configOptions
     * @param $index
     * @return SearchQueryTerm
     */
    public function getWhatItShouldBe($token, $configOptions, $index): SearchQueryTerm
    {
        // Get whether the data provider gave us custom config options for this term based on the above searchParam
        $config = $this->getConfigFromOptions($index, $configOptions);

        return $this->createDefaultSearchQueryTermFromString($token->term, $config);
    }

    /**
     * @param $term
     * @param $config
     * @return SearchQueryTerm
     */
    public function createDefaultSearchQueryTermFromString(string $term, array $config): SearchQueryTerm
    {
        if (!isset($config['term'])) {
            $config['term'] = $term;
        }

        return new SearchQueryTerm($config);
    }

    /**
     * Essentially a function that sees if the $key exists in the $config options and returns that. If it doesnt exist it returns
     * self::DEFAULT_SEARCH_QUERY_TERM_CONFIG
     *
     * @param string|null $key
     * @param array|null $configOptions
     * @return array|mixed
     */
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

    /**
     * Compare two searchQueryTerm objects to make sure they are the same.
     *
     * @param SearchQueryTerm $one
     * @param SearchQueryTerm $two
     */
    public function ensureIdenticalSearchTermObjects(SearchQueryTerm $one, SearchQueryTerm $two)
    {
        $this->assertSame([
            $one->exclude, $one->exact, $one->subLeft, $one->subRight, $one->attribute, $one->term, $one->phrase
        ], [$two->exclude, $two->exact, $two->subLeft, $two->subRight, $two->attribute, $two->term, $two->phrase]);
    }

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testSearchQueryGrouping()
    {
        $search = new SearchQuery('i live OR die');

        $options = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $options['term'] = 'i';

        $this->ensureIdenticalSearchTermObjects(new SearchQueryTerm($options), $search->getTokens()[0]);

        $this->assertInstanceOf(SearchQueryTermGroup::class, $search->getTokens()[1]);

        $options['term'] = 'live';
        $this->ensureIdenticalSearchTermObjects(new SearchQueryTerm($options), $search->getTokens()[1]->terms[0]);

        $options['term'] = 'die';
        $this->ensureIdenticalSearchTermObjects(new SearchQueryTerm($options), $search->getTokens()[1]->terms[1]);
    }

    /**
     *
     */
    public function testOnlyOr()
    {
        $search = new SearchQuery('OR');
        $this->assertSame([], $search->getTokens());
    }

    /*
     * Test that additional default _termOptions are respected
     */
    public function testAdditionalDefaultTerms()
    {
        $search = new SearchQuery('search', [
            'exclude' => true,
            'exact' => true,
            'subLeft' => true,
            'subRight' => true,
            'attribute' => [],
            'phrase' => [],
        ]);

        $this->ensureIdenticalSearchTermObjects($search->getTokens()[0], new SearchQueryTerm([
            'exclude' => true,
            'exact' => true,
            'subLeft' => true,
            'subRight' => true,
            'attribute' => [],
            'term' => 'search',
            'phrase' => [],
        ]));
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
     * @dataProvider searchQueryDataProviders
     *
     * @param string $query
     * @param array $configOptions
     * @param int|null $sizeOfArray
     */
    public function testSearchQuery(string $query, array $configOptions = null, int $sizeOfArray = null)
    {
        $search = new SearchQuery($query);

        // If we have to count the array. Count the array.
        if ($sizeOfArray !== null) {
            $this->assertCount($sizeOfArray, $search->getTokens());
        }

        // Loop through the given tokens.
        foreach ($search->getTokens() as $index => $token) {
            $whatItShouldBe = $this->getWhatItShouldBe($token, $configOptions, $index);

            $this->ensureIdenticalSearchTermObjects($whatItShouldBe, $token);
        }
    }

    /**
     * @dataProvider searchQueryDataProviders
     *
     * @param string $query
     * @param array|null $configOptions
     */
    public function testSearchQuerySortOrder(string $query, array $configOptions = null)
    {
        $exploded = explode(' ', $query);
        foreach ((new SearchQuery($query))->getTokens() as $index => $token) {
            $config = $this->getConfigFromOptions($index, $configOptions);

            $fromExplodedString = $this->createDefaultSearchQueryTermFromString($exploded[$index], $config);
            $this->ensureIdenticalSearchTermObjects($fromExplodedString, $token);
        }
    }

    // Data Providers
    // =========================================================================

    /**
     *
     */
    public function searchQueryDataProviders(): array
    {
        // The $searchQueryTerm->term property will not contain the "" double quotes and will have ['phrase'] set to true
        $quotedPhraseConfig = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $quotedPhraseConfig['phrase'] = true;
        $quotedPhraseConfig['term'] = 'Hello';


        $excludeTermConfig = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $excludeTermConfig['exclude'] = true;
        $excludeTermConfig['term'] = 'Hello';


        $subtermLeft = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $subtermLeft['subLeft'] = true;
        $subtermLeft['term'] = 'Hello';

        $subTermRight = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $subTermRight['term'] = 'Hello';

        $firstQuote = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $firstQuote['term'] = 'i';
        $firstQuote['phrase'] = true;

        $attributeConfig = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $attributeConfig['term'] = 'test';
        $attributeConfig['attribute'] = 'body';
        $attributeConfig['exact'] = true;
        $attributeConfig['subRight'] = false;


        $attributePhraseConfig = $attributeConfig;
        $attributePhraseConfig['phrase'] = true;

        $emptyConfig = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $emptyConfig['term'] = '';
        $emptyConfig['exclude'] = true;
        $emptyConfig['subRight'] = false;
        $emptyConfig['subLeft'] = true;
        $emptyConfig['attribute'] = 'body';

        return [
            ['i said "Hello"', ['2' => $quotedPhraseConfig], 3],
            ['i said \'Hello\'', ['2' => $quotedPhraseConfig], 3],
            ['i said -Hello', ['2' => $excludeTermConfig], 3],
            ['i said *Hello', ['2' => $subtermLeft], 3],
            ['i said Hello*', ['2' => $subTermRight], 3],
            ['i said *Hello*', ['2' => $subtermLeft], 3],
            ['i said body::"test"', ['2' => $attributePhraseConfig], 3],
            ['i said -body:*', ['2' => $emptyConfig], 3],
            ['i said body::test', ['2' => $attributeConfig], 3],

            ['i have spaces and lines', null, 5],
            ['"i" said Hello', ['0' => $firstQuote], 3]
        ];
    }
}
