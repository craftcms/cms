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
    public function tryToForgetQuery(FunctionalTester $I)
    {
        $I->amOnPage('?action=gql');
        $I->see('Request missing required param');
    }
}
