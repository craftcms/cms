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
use FunctionalTester;

/**
 * Test that most pages within Craft are rendered successfully and that we can - some a minor degree -
 * establish that the correct content is loaded on those pages.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PageRenderChecksCest
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $cpTrigger;

    /**
     * @var
     */
    public $currentUser;

    // Public Methods
    // =========================================================================

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $this->currentUser = User::find()
            ->admin()
            ->one();

        $I->amLoggedInAs($this->currentUser);
        $this->cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
    }

    // Tests
    // =========================================================================

    /**
     * @param FunctionalTester $I
     * @param Example $example
     * @dataProvider pagesDataProvider
     */
    public function test200Page(FunctionalTester $I, Example $example)
    {
        $I->amOnPage('/'.$this->cpTrigger.$example['url']);
        $I->seeInTitle($example['title']);
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

    /**
     * @return array
     */
    protected function pagesDataProvider() : array
    {
        return [
            ['url' => '/dashboard', 'title' => 'Dashboard'],
            ['url' => '/entries', 'title' => 'Entries'],
            ['url' => '/users', 'title' => 'Users'],
            // TODO: fix globals fixture     ['url' => '/globals', 'title' => 'Globals'],
            // TODO: Requires fixtures data. ['url' => '/categories', 'title' => 'Categories'],

            // Settings pages
            ['url' => '/settings/general', 'title' => 'General Settings', 'extraContent' => [
                'System Name',
                'System Status',
                'Time Zone',
                'Login Page Logo'
            ]],
            ['url' => '/settings/sections', 'title' => 'Sections', 'extraContent' => [
                'Craft CMS Test Section'
            ]],
            ['url' => '/settings/users', 'title' => 'User Settings', 'extraContent' => [
                'User Groups',
                'Fields',
                'Test group1'
            ]],
            ['url' => '/settings/users/settings', 'title' => 'User Settings', 'extraContent' => [
                'User Photo Location',
                'Verify email addresses?',
                'Allow public registration?'
            ]],
            ['url' => '/settings/users/fields', 'title' => 'User Settings', 'extraContent' => [
                'Design your field layout'
            ]],

            ['url' => '/settings/email', 'title' => 'Email Settings', 'extraContent' => [
                'Email Settings',
                'This can be set to an environment variable. Learn more',
                'Transport Type'
            ]],
            ['url' => '/settings/plugins', 'title' => 'Plugins'],
            ['url' => '/settings/sites', 'title' => 'Sites', 'extraContent' => [
                'Craft CMS Test Site'
            ]],
            ['url' => '/settings/routes', 'title' => 'Routes', 'extraContent' => [
                '_includes/route-handler'
            ]],
            ['url' => '/settings/routes', 'title' => 'Routes', 'extraContent' => [
                '_includes/route-handler'
            ]],
            ['url' => '/settings/fields', 'title' => 'Fields', 'extraContent' => [
                'Test field group 1',
                'Example text field 1'
            ]],
            ['url' => '/settings/assets', 'title' => 'Asset Settings', 'extraContent' => [
                'Test volume 1',
                'Volumes',
                'Image Transforms'
            ]],
            ['url' => '/settings/assets/transforms', 'title' => 'Asset Settings', 'extraContent' => [
                'Example transform 1'
            ]],

            // Utility pages
            ['url' => '/utilities', 'title' => 'System Report', 'extraContent' => [
                'Application Info',
                'Yii version',
                'Plugins',
                'Requirements'
            ]],
            ['url' => '/utilities/updates', 'title' => 'Updates', 'extraContent' => [
                'Craft CMS',
                'Update'
            ]],
            ['url' => '/utilities/system-messages', 'title' => 'System Messages', 'extraContent' => [
                'When someone creates an account'
            ]],
            ['url' => '/utilities/asset-indexes', 'title' => 'Asset Indexes', 'extraContent' => [
                'Test volume 1'
            ]],
            ['url' => '/utilities/deprecation-errors', 'title' => 'Deprecation Warnings', 'extraContent' => [
                'No deprecation errors to report!'
            ]],
            ['url' => '/utilities/find-replace', 'title' => 'Find and Replace', 'extraContent' => [
                'Find Text',
                'Replace Text'
            ]],
            ['url' => '/utilities/migrations', 'title' => 'Migrations', 'extraContent' => [
                'No content migrations.'
            ]],
            ['url' => '/utilities/clear-caches', 'title' => 'Clear Caches', 'extraContent' => [
                'Asset caches'
            ]],
            ['url' => '/utilities/db-backup', 'title' => 'Database Backup', 'extraContent' => [
                'Download backup?'
            ]],
        ];
    }
}
