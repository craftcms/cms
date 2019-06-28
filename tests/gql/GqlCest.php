<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace tests\gql;

use Craft;
use crafttests\fixtures\AssetsFixture;
use crafttests\fixtures\EntryWithFieldsFixture;
use crafttests\fixtures\GlobalSetFixture;
use crafttests\fixtures\GqlTokensFixture;
use FunctionalTester;

class GqlCest
{
    public function _fixtures()
    {
        return [
            'entriesWithField' => [
                'class' => EntryWithFieldsFixture::class
            ],
            'gqlTokens' => [
                'class' => GqlTokensFixture::class
            ],
            'globalSets' => [
                'class' => GlobalSetFixture::class
            ]
        ];
    }

    public function _before(FunctionalTester $I)
    {
        $this->_setToken('My+voice+is+my+passport.+Verify me.');
    }

    public function _after(FunctionalTester $I)
    {
        $gqlService = Craft::$app->getGql();
        $gqlService->flushCaches();
    }

    public function _setToken(string $accessToken)
    {
        $gqlService = Craft::$app->getGql();
        $token = $gqlService->getTokenByAccessToken($accessToken);
        $gqlService->setToken($token);
    }

    /**
     * Test whether missing query parameter is handled correctly.
     */
    public function forgetQueryParameter(FunctionalTester $I)
    {
        $I->amOnPage('?action=gql');
        $I->see('Request missing required param');
    }

    /**
     * Test whether malformed query parameter is handled correctly.
     */
    public function provideMalformedQueryParameter(FunctionalTester $I)
    {
        $I->amOnPage('?action=gql&query=bogus}');
        $I->see('Syntax Error');
    }

    /**
     * Test whether all query types work correctly
     */
    public function testQuerying(FunctionalTester $I)
    {

        $queryTypes = [
            'Entries',
            'Users',
            'Assets',
            'GlobalSets',
        ];

        foreach ($queryTypes as $queryType) {
            $I->amOnPage('?action=gql&query={query' . $queryType . '{title}}');
            $I->see('"query' . $queryType . '":[');
        }
    }

    /**
     * Test whether querying for wrong gql field returns the correct error.
     */
    public function testWrongGqlField(FunctionalTester $I)
    {
        $parameter = 'bogus';
        $I->amOnPage('?action=gql&query={queryEntries{' . $parameter . '}}');
        $I->see('"Cannot query field \"' . $parameter . '\"');
    }

    /**
     * Test whether querying with wrong parameters returns the correct error.
     */
    public function testWrongGqlQueryParameter(FunctionalTester $I)
    {
        $I->amOnPage('?action=gql&query={queryEntries(limit:[5,2]){title}}');
        $I->see('"debugMessage":"Expected');
    }

    /**
     * Test whether query results yield the expected results.
     */
    public function testQueryResults(FunctionalTester $I)
    {
        $testData = file_get_contents(__DIR__ . '/data/gql.txt');
        foreach (explode('-----TEST DELIMITER-----', $testData) as $case) {
            list ($query, $response) = explode('-----RESPONSE DELIMITER-----', $case);
            list ($token, $query) = explode('-----TOKEN DELIMITER-----', $query);
            $this->_setToken(trim($token));
            $I->amOnPage('?action=gql&query='.urlencode(trim($query)));
            $I->see(trim($response));
            $gqlService = Craft::$app->getGql();
            $gqlService->flushCaches();
        }
    }
}
