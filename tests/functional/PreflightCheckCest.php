<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

use Codeception\Example;
use Craft;
use craft\elements\User;
use crafttests\fixtures\GlobalSetFixture;
use FunctionalTester;

/**
 * Class PreflightCheckCest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PreflightCheckCest
{
    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $userEl = User::find()
            ->admin()
            ->one();

        Craft::$app->getUser()->setIdentity($userEl);
    }

    /**
     * @param FunctionalTester $I
     * @param Example $example
     * @dataProvider pagesDataProvider
     */
    public function test200Page(FunctionalTester $I, Example $example)
    {
        $adminTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;

        $I->amOnPage('?p=/'.$adminTrigger.''.$example['url']);
        $I->see($example['title']);
        $I->seeResponseCodeIs(200);

        if (isset($example['extraContent'])) {
            foreach ($example['extraContent'] as $extraContent) {
                $I->see($extraContent);
            }
        }
    }

    // Protected Methods
    // =========================================================================

    // Data providers
    // =========================================================================

    protected function pagesDataProvider() : array
    {
        return [
            ['url' => '/dashboard', 'title' => 'Dashboard'],
            ['url' => '/entries', 'title' => 'Entries'],
            ['url' => '/users', 'title' => 'Users'],
       // TODO: fix globals fixture     ['url' => '/globals', 'title' => 'Globals'],
            // todo: Requires fixtures data. ['url' => '/categories', 'title' => 'Categories'],
            ['url' => '/settings/plugins', 'title' => 'Plugins'],
            ['url' => '/settings/sections', 'title' => 'Sections', 'extraContent' => [
                'Craft CMS Test section'
            ]],
            ['url' => '/settings/sites', 'title' => 'Sites', 'extraContent' => [
                'Craft CMS testing'
            ]],
            ['url' => '/utilities', 'title' => 'Utilities']
        ];
    }
}
