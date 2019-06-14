<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace tests\functional;

use crafttests\fixtures\EntryFixture;
use crafttests\fixtures\EntryWithFieldsFixture;
use FunctionalTester;

class GqlCest
{
    public function _fixtures()
    {
        return [
            'entriesWithField' => [
                'class' => EntryWithFieldsFixture::class
            ],
        ];
    }

    public function _before(FunctionalTester $I)
    {
    }

    /**
     * Test whether missing query parameter is handled correctly.
     */
    public function forgetQueryParameter(FunctionalTester $I)
    {
        // If this suite is ran separately, sometimes this test fails for no reason.
        // ¯\_(ツ)_/¯
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
            'MatrixBlocks',
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
            $I->amOnPage('?action=gql&query='.urlencode(trim($query)));
            $I->see(trim($response));
        }
    }
}
