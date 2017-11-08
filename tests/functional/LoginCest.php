<?php

namespace tests\functional;

use FunctionalTester;

class LoginCest
{
    public function _before(FunctionalTester $I)
    {
    }

    public function _after(FunctionalTester $I)
    {
    }

    // tests
    public function seeTemplateHomepageTest(FunctionalTester $I)
    {
        $I->amOnPage('/');
        $I->see('Hello', 'h1');
    }
}
