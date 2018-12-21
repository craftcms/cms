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
     * Tests that SearchQueryTerms are created as indivual objects if the query param has spaces
     */
    public function testMultilineQuery()
    {
        $query = 'i have spaces and lines';

        $this->compareObjects($query);
    }


    public function compareObjects(string $query, array $searchQueryTermConfig = null)
    {
        $exploded = explode(' ', $query);

        if ($searchQueryTermConfig) {
            $config = $searchQueryTermConfig;
        } else {
            $config = [
                'exclude' => false,
                'exact' => false,
                'subLeft' => false,
                'subRight' => true,
                'attribute' => null,
                'phrase' => null
            ];
        }

        $createDefaultFromString = function ($string) use($config) {
            $searchConf = $config;
            $searchConf['term'] = $string;
            return new SearchQueryTerm($searchConf);
        };


        $search = new SearchQuery($query);

        foreach ($search->getTokens() as $index => $token) {
            $whatItShouldBe = $createDefaultFromString($token->term);
            $whatItIs = $token;

            $this->ensureIdenticalSearchTermObjects($whatItShouldBe, $whatItIs);

            // Test that the index of the searchQueryTerm's is in the correct order by comparing to the exploded string.
            $fromExplodedString = $createDefaultFromString($exploded[$index]);
            $this->ensureIdenticalSearchTermObjects($fromExplodedString, $whatItIs);
        }
    }

    public function ensureIdenticalSearchTermObjects(SearchQueryTerm $one, SearchQueryTerm $two)
    {
        $this->assertSame([
            $one->exclude, $one->exact, $one->subLeft, $one->subRight,$one->attribute, $one->term, $one->phrase
        ], [$two->exclude, $two->exact, $two->subLeft, $two->subRight, $two->attribute, $two->term, $two->phrase]);
    }
}