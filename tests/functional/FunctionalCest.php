<?php

namespace tests\functional;

use craft\elements\User;
use FunctionalTester;

class FunctionalCest
{
    public function _before(\AcceptanceTester $I)
    {
        $user = User::find()
            ->admin()
            ->one();

        \Craft::$app->getUser()->setIdentity($user);
    }
    public function seeSections(FunctionalTester $I)
    {
        $I->amOnPage('?p=/adminustriggerus/settings/sections');
        $I->see('Craft CMS Test section');
    }

    public function seeTemplateHomepageTest(FunctionalTester $I)
    {
         $I->amOnPage('?p=/adminustriggerus/entries');
         $I->see('Craft CMS');
    }

    public function seeSettingsPage(FunctionalTester $I)
    {
        $I->amOnPage('?p=/adminustriggerus/settings');
        $I->see('Settings');
    }
}
