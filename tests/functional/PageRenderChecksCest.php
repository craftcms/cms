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
    /**
     * @var string
     */
    public $cpTrigger;

    /**
     * @var
     */
    public $currentUser;

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

    /**
     * @param FunctionalTester $I
     * @param Example $example
     * @dataProvider pagesDataProvider
     */
    public function test200Page(FunctionalTester $I, Example $example)
    {
        $I->amOnPage('/' . $this->cpTrigger . $example['url']);
        $I->seeInTitle($example['title']);
        $I->seeResponseCodeIs(200);

        if (isset($example['extraContent'])) {
            foreach ($example['extraContent'] as $extraContent) {
                if (isset($extraContent['rendered'])) {
                    $I->see($extraContent['rendered']);
                } else {
                    $I->seeInSource($extraContent['source']);
                }
            }
        }
    }

    /**
     * @return array
     */
    protected function pagesDataProvider(): array
    {
        return [
            ['url' => '/dashboard', 'title' => 'Dashboard'],
            ['url' => '/entries', 'title' => 'Entries'],
            ['url' => '/users', 'title' => 'Users'],
            // TODO: fix globals fixture     ['url' => '/globals', 'title' => 'Globals'],
            // TODO: Requires fixtures data. ['url' => '/categories', 'title' => 'Categories'],

            // Settings pages
            [
                'url' => '/settings/general', 'title' => 'General Settings', 'extraContent' => [
                ['rendered' => 'System Name'],
                ['rendered' => 'System Status'],
                ['rendered' => 'Time Zone'],
                ['rendered' => 'Login Page Logo']
            ]
            ],
            [
                'url' => '/settings/sections', 'title' => 'Sections', 'extraContent' => [
                ['source' => 'Craft CMS Test Section']
            ]
            ],
            [
                'url' => '/settings/users', 'title' => 'User Settings', 'extraContent' => [
                ['rendered' => 'User Groups'],
                ['rendered' => 'Fields'],
                ['source' => 'Test group1']
            ]
            ],
            [
                'url' => '/settings/users/settings', 'title' => 'User Settings', 'extraContent' => [
                ['rendered' => 'User Photo Location'],
                ['rendered' => 'Verify email addresses?'],
                ['rendered' => 'Allow public registration?']
            ]
            ],
            [
                'url' => '/settings/users/fields', 'title' => 'User Settings', 'extraContent' => [
                ['rendered' => 'Design your field layout']
            ]
            ],

            [
                'url' => '/settings/email', 'title' => 'Email Settings', 'extraContent' => [
                ['rendered' => 'Email Settings'],
                ['rendered' => 'This can be set to an environment variable. Learn more'],
                ['rendered' => 'Transport Type']
            ]
            ],
            ['url' => '/settings/plugins', 'title' => 'Plugins'],
            [
                'url' => '/settings/sites', 'title' => 'Sites', 'extraContent' => [
                ['source' => 'Craft CMS Test Site']
            ]
            ],
            [
                'url' => '/settings/routes', 'title' => 'Routes', 'extraContent' => [
                ['rendered' => '_includes/route-handler']
            ]
            ],
            [
                'url' => '/settings/routes', 'title' => 'Routes', 'extraContent' => [
                ['rendered' => '_includes/route-handler']
            ]
            ],
            [
                'url' => '/settings/fields', 'title' => 'Fields', 'extraContent' => [
                ['source' => 'Test field group 1'],
                ['source' => 'Example text field 1']
            ]
            ],
            [
                'url' => '/settings/assets', 'title' => 'Asset Settings', 'extraContent' => [
                ['source' => 'Test volume 1'],
                ['rendered' => 'Volumes'],
                ['rendered' => 'Image Transforms']
            ]
            ],
            [
                'url' => '/settings/assets/transforms', 'title' => 'Asset Settings', 'extraContent' => [
                ['source' => 'Example transform 1']
            ]
            ],

            // Utility pages
            [
                'url' => '/utilities', 'title' => 'System Report', 'extraContent' => [
                ['rendered' => 'Application Info'],
                ['rendered' => 'Yii version'],
                ['rendered' => 'Plugins'],
                ['rendered' => 'Requirements']
            ]
            ],
            [
                'url' => '/utilities/updates', 'title' => 'Updates', 'extraContent' => [
                ['rendered' => 'Craft CMS'],
                ['rendered' => 'Update']
            ]
            ],
            [
                'url' => '/utilities/system-messages', 'title' => 'System Messages', 'extraContent' => [
                ['rendered' => 'When someone creates an account']
            ]
            ],
            [
                'url' => '/utilities/asset-indexes', 'title' => 'Asset Indexes', 'extraContent' => [
                ['source' => 'Test volume 1']
            ]
            ],
            [
                'url' => '/utilities/deprecation-errors', 'title' => 'Deprecation Warnings', 'extraContent' => [
                ['rendered' => 'No deprecation errors to report!']
            ]
            ],
            [
                'url' => '/utilities/find-replace', 'title' => 'Find and Replace', 'extraContent' => [
                ['rendered' => 'Find Text'],
                ['rendered' => 'Replace Text']
            ]
            ],
            [
                'url' => '/utilities/migrations', 'title' => 'Migrations', 'extraContent' => [
                ['rendered' => 'No content migrations.']
            ]
            ],
            [
                'url' => '/utilities/clear-caches', 'title' => 'Clear Caches', 'extraContent' => [
                ['rendered' => 'Asset caches']
            ]
            ],
            [
                'url' => '/utilities/db-backup', 'title' => 'Database Backup', 'extraContent' => [
                ['rendered' => 'Download backup?']
            ]
            ],
        ];
    }
}
