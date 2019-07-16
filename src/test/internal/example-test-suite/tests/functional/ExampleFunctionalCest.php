<?php

namespace myprojecttests;

use Craft;
use craft\elements\User;
use FunctionalTester;

class ExampleFunctionalCest
{
    // Public methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @param FunctionalTester $I
     */
    public function testCraftEdition(FunctionalTester $I)
    {
        $I->amOnPage('?p=/');
        $I->seeResponseCodeIs(200);
    }
}
