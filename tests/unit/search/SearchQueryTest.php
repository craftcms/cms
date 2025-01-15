<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\search;

use craft\helpers\ArrayHelper;
use craft\search\SearchQuery;
use craft\search\SearchQueryTerm;
use craft\search\SearchQueryTermGroup;
use craft\test\TestCase;

/**
 * Unit tests for SearchTest
 *
 * Searching and some of the commands run in this test are documented here:
 * https://craftcms.com/docs/5.x/system/searching.html
 *
 * @todo There are MySQL and PostgreSQL specific search tests that need to be performed.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SearchQueryTest extends TestCase
{
    public const DEFAULT_SEARCH_QUERY_TERM_CONFIG = [
        'exclude' => false,
        'exact' => false,
        'subLeft' => false,
        'subRight' => true,
        'phrase' => false,
    ];

    /**
     * @param SearchQueryTerm $token
     * @param array|null $configOptions
     * @param string|null $index
     * @return SearchQueryTerm
     */
    public function getWhatItShouldBe(SearchQueryTerm $token, ?array $configOptions, ?string $index): SearchQueryTerm
    {
        // Get whether the data provider gave us custom config options for this term based on the above searchParam
        $config = $configOptions[$index] ?? self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        return $this->createDefaultSearchQueryTermFromString($token->term, $config);
    }

    /**
     * @param string $term
     * @param array $config
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
     * Compare two searchQueryTerm objects to make sure they are the same.
     *
     * @param SearchQueryTerm $one
     * @param SearchQueryTerm $two
     */
    public function ensureIdenticalSearchTermObjects(SearchQueryTerm $one, SearchQueryTerm $two)
    {
        $properties = ['subLeft', 'subRight', 'exclude', 'exact', 'attribute', 'term', 'phrase'];
        self::assertSame(ArrayHelper::toArray($one, $properties), ArrayHelper::toArray($two, $properties));
    }

    /**
     *
     */
    public function testSearchQueryGrouping(): void
    {
        $search = new SearchQuery('i live OR die');

        $options = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $options['term'] = 'i';

        $this->ensureIdenticalSearchTermObjects(new SearchQueryTerm($options), $search->getTokens()[0]);

        self::assertInstanceOf(SearchQueryTermGroup::class, $search->getTokens()[1]);

        $options['term'] = 'live';
        $this->ensureIdenticalSearchTermObjects(new SearchQueryTerm($options), $search->getTokens()[1]->terms[0]);

        $options['term'] = 'die';
        $this->ensureIdenticalSearchTermObjects(new SearchQueryTerm($options), $search->getTokens()[1]->terms[1]);
    }

    /**
     *
     */
    public function testOnlyOr(): void
    {
        $search = new SearchQuery('OR');
        self::assertSame([], $search->getTokens());
    }

    /*
     * Test that additional default _termOptions are respected
     */
    public function testAdditionalDefaultTerms(): void
    {
        $search = new SearchQuery('search', [
            'exclude' => true,
            'exact' => true,
            'subLeft' => true,
            'subRight' => true,
            'phrase' => false,
        ]);

        $this->ensureIdenticalSearchTermObjects($search->getTokens()[0], new SearchQueryTerm([
            'exclude' => true,
            'exact' => true,
            'subLeft' => true,
            'subRight' => true,
            'term' => 'search',
            'phrase' => false,
        ]));
    }

    /**
     * Test the defaults of the SearchQuery class
     */
    public function testDefaultQueryTokens(): void
    {
        $search = new SearchQuery('search');

        self::assertSame('search', $search->getQuery());
        self::assertInstanceOf(SearchQueryTerm::class, $search->getTokens()[0]);

        $searchDefaults = new SearchQueryTerm([
            'exclude' => false,
            'exact' => false,
            'subLeft' => false,
            'subRight' => true,
            'term' => $search->getQuery(),
            'phrase' => false,
        ]);

        $tokens = $search->getTokens()[0];

        $this->ensureIdenticalSearchTermObjects($searchDefaults, $tokens);
    }

    /**
     * @dataProvider searchQueryDataProviders
     * @param string $query
     * @param array|null $configOptions
     * @param int|null $sizeOfArray
     */
    public function testSearchQuery(string $query, ?array $configOptions = null, int $sizeOfArray = null): void
    {
        $search = new SearchQuery($query);

        // If we have to count the array. Count the array.
        if ($sizeOfArray !== null) {
            self::assertCount($sizeOfArray, $search->getTokens());
        }

        // Loop through the given tokens.
        foreach ($search->getTokens() as $index => $token) {
            $whatItShouldBe = $this->getWhatItShouldBe($token, $configOptions, $index);

            $this->ensureIdenticalSearchTermObjects($whatItShouldBe, $token);
        }
    }

    /**
     * @dataProvider searchQueryDataProviders
     * @param string $query
     * @param array|null $configOptions
     */
    public function testSearchQuerySortOrder(string $query, array $configOptions = null): void
    {
        $exploded = explode(' ', $query);
        foreach ((new SearchQuery($query))->getTokens() as $index => $token) {
            $config = $configOptions[$index] ?? self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
            $fromExplodedString = $this->createDefaultSearchQueryTermFromString($exploded[$index], $config);
            $this->ensureIdenticalSearchTermObjects($fromExplodedString, $token);
        }
    }

    /**
     *
     */
    public static function searchQueryDataProviders(): array
    {
        // The $searchQueryTerm->term property will not contain the "" double quotes and will have ['phrase'] set to true
        $quotedPhraseConfig = array_merge(self::DEFAULT_SEARCH_QUERY_TERM_CONFIG);
        $quotedPhraseConfig['phrase'] = true;
        $quotedPhraseConfig['term'] = 'Hello';

        $excludeTermConfig = array_merge(self::DEFAULT_SEARCH_QUERY_TERM_CONFIG);
        $excludeTermConfig['exclude'] = true;
        $excludeTermConfig['term'] = 'Hello';

        $subtermLeft = array_merge(self::DEFAULT_SEARCH_QUERY_TERM_CONFIG);
        $subtermLeft['subLeft'] = true;
        $subtermLeft['subRight'] = false;
        $subtermLeft['term'] = 'Hello';

        $subTermRight = array_merge(self::DEFAULT_SEARCH_QUERY_TERM_CONFIG);
        $subTermRight['term'] = 'Hello';

        $subtermBoth = array_merge(self::DEFAULT_SEARCH_QUERY_TERM_CONFIG);
        $subtermBoth['subLeft'] = true;
        $subtermBoth['subRight'] = true;
        $subtermBoth['term'] = 'Hello';

        $firstQuote = array_merge(self::DEFAULT_SEARCH_QUERY_TERM_CONFIG);
        $firstQuote['term'] = 'i';
        $firstQuote['phrase'] = true;

        $attributeConfig = array_merge(self::DEFAULT_SEARCH_QUERY_TERM_CONFIG);
        $attributeConfig['term'] = 'test';
        $attributeConfig['attribute'] = 'body';
        $attributeConfig['exact'] = true;
        $attributeConfig['subRight'] = false;

        $attributePhraseConfig = array_merge($attributeConfig);
        $attributePhraseConfig['phrase'] = true;

        $emptyConfig = array_merge(self::DEFAULT_SEARCH_QUERY_TERM_CONFIG);
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
            ['i said *Hello*', ['2' => $subtermBoth], 3],
            ['i said body::"test"', ['2' => $attributePhraseConfig], 3],
            ['i said -body:*', ['2' => $emptyConfig], 3],
            ['i said body::test', ['2' => $attributeConfig], 3],

            ['i have spaces and lines', null, 5],
            ['"i" said Hello', ['0' => $firstQuote], 3],
        ];
    }
}
