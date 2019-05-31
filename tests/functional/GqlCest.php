<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace tests\functional;

use craftunit\fixtures\EntryFixture;
use FunctionalTester;

class GqlCest
{
    public function _before(FunctionalTester $I)
    {
    }

    // tests
    public function forgetQueryParameter(FunctionalTester $I)
    {
        // If this suite is ran separately, somethis this test fails for no reason.
        // ¯\_(ツ)_/¯
        $I->amOnPage('?action=gql');
        $I->see('Request missing required param');
    }

    public function provideMalformedQueryParameter(FunctionalTester $I)
    {
        $I->amOnPage('?action=gql&query=bogus}');
        $I->see('Syntax Error');
    }

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

    public function testWrongGqlField(FunctionalTester $I)
    {
        $parameter = 'bogus';
        $I->amOnPage('?action=gql&query={queryEntries{' . $parameter . '}}');
        $I->see('"Cannot query field \"' . $parameter . '\"');
    }

    public function testWrongGqlQueryParameter(FunctionalTester $I)
    {
        $resp = $I->amOnPage('?action=gql&query={queryEntries(limit:[5,2]){title}}');
        $I->see('"debugMessage":"Expected');
    }

    // @todo: figure out how to test paging. Probably after we have fixtures for a lot of stuff.
//    public function testPaging(FunctionalTester $I)
//    {
//        $I->amOnPage('?action=gql&query={queryEntries(limit:2){title}}');
//        $I->see('"debugMessage":"Expected');
//    }
}
