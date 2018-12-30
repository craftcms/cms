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
use craft\search\SearchQueryTermGroup;

/**
 * Unit tests for SearcTest
 *
 * Searching and some of the commands run in this test are documented here:
 * https://docs.craftcms.com/v3/searching.html#supported-syntaxes
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

    public function testQueryGroupData() {
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
     *
     */
    public function searchQueryData()
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

        $attributeConfig = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $attributeConfig['term'] = 'test';
        $attributeConfig['phrase'] = true;
        $attributeConfig['attribute'] = 'body';
        $attributeConfig['exact'] = true;

        $emptyConfig = self::DEFAULT_SEARCH_QUERY_TERM_CONFIG;
        $emptyConfig['term'] = '';
        $emptyConfig['exclude'] = true;
        $emptyConfig['subRight'] = false;
        $emptyConfig['subLeft'] = true;
        $emptyConfig['attribute'] = 'body';

        return [
            ['i said "Hello"', ['Hello' => $quotedPhraseConfig], 3],
            ['i said \'Hello\'', ['Hello' => $quotedPhraseConfig], 3],
            ['i said -Hello', ['Hello' => $excludeTermConfig], 3],
            ['i said *Hello', ['Hello' => $subtermLeft], 3],
            ['i said Hello*', ['Hello' => $subTermRight], 3],
            ['i said *Hello*', ['Hello' => $subtermLeft], 3],
            ['i said body::"test"', ['test' => $attributeConfig], 3],
            ['i said -body:*', ['index2' => $emptyConfig], 3],

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

        $search = new SearchQuery($query);

        // If we have to count the array. Count the array.
        if ($sizeOfArray !== null){
            $this->assertCount($sizeOfArray, $search->getTokens());
        }

        // Loop through the given tokens.
        foreach ($search->getTokens() as $index => $token) {

            // If token term is an empty sring we try to find a config option by index+index_number
            $searchTerm = $token->term;
            if (!$searchTerm || $searchTerm === '') {
                $searchTerm = 'index'.$index.'';
            }

            // Get wether the data provider gave us custom config options for this term based on the above searchParam
            $config = $this->getConfigFromOptions($searchTerm, $configOptions);

            // Setup the token objects for comparison based on the config above and the term of the token
            $whatItShouldBe = $this->createDefaultTokenFromString($token->term, $config);
            $whatItIs = $token;

            $this->ensureIdenticalSearchTermObjects($whatItShouldBe, $whatItIs);

            // Test that the index of the searchQueryTerm's is in the correct order by comparing to the exploded string.
            $fromExplodedString = $this->createDefaultTokenFromString($exploded[$index], $config);
            $this->ensureIdenticalSearchTermObjects($fromExplodedString, $whatItIs);
        }
    }

    /**
     * @param $string
     * @param $config
     * @return SearchQueryTerm
     */
    public function createDefaultTokenFromString($string, $config) : SearchQueryTerm
    {
        if (!isset($config['term'])) {
            $config['term'] = $string;
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
     * @param SearchQueryTerm $one
     * @param SearchQueryTerm $two
     */
    public function ensureIdenticalSearchTermObjects(SearchQueryTerm $one, SearchQueryTerm $two)
    {
        $this->assertSame([
            $one->exclude, $one->exact, $one->subLeft, $one->subRight,$one->attribute, $one->term, $one->phrase
        ], [$two->exclude, $two->exact, $two->subLeft, $two->subRight, $two->attribute, $two->term, $two->phrase]);
    }
}