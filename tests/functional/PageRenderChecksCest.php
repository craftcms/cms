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
use craft\enums\CmsEdition;
use FunctionalTester;

/**
 * Test that most pages within Craft are rendered successfully and the correct content is loaded on those pages.
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
    public string $cpTrigger;

    /**
     * @var User|null
     */
    public ?User $currentUser;

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

        Craft::$app->edition = CmsEdition::Pro;
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

            // Settings pages
            [
                'url' => '/settings/general', 'title' => 'General Settings', 'extraContent' => [
                ['rendered' => 'System Name'],
                ['rendered' => 'System Status'],
                ['rendered' => 'Retry Duration'],
                ['rendered' => 'Time Zone'],
                ['rendered' => 'Login Page Logo'],
                ['rendered' => 'Site Icon'],
            ],
            ],
            [
                'url' => '/settings/sections', 'title' => 'Sections', 'extraContent' => [
                ['rendered' => 'New section'],
            ],
            ],
            [
                'url' => '/settings/users', 'title' => 'User Settings', 'extraContent' => [
                ['rendered' => 'User Groups'],
                ['rendered' => 'Fields'],
                ['rendered' => 'Settings'],
                ['rendered' => 'New user group'],
            ],
            ],
            [
                'url' => '/settings/users/settings', 'title' => 'User Settings', 'extraContent' => [
                ['rendered' => 'User Photo Volume'],
                ['rendered' => 'Verify email addresses'],
                ['rendered' => 'Allow public registration'],
            ],
            ],
            [
                'url' => '/settings/users/fields', 'title' => 'User Settings', 'extraContent' => [
                ['rendered' => 'Field Layout'],
            ],
            ],

            [
                'url' => '/settings/email', 'title' => 'Email Settings', 'extraContent' => [
                ['rendered' => 'System Email Address'],
                ['rendered' => 'This can begin with an environment variable. Learn more'],
                ['rendered' => 'Sender Name'],
                ['rendered' => 'HTML Email Template'],
                ['rendered' => 'Transport Type'],
            ],
            ],
            [
                'url' => '/settings/plugins', 'title' => 'Plugins',
            ],
            [
                'url' => '/settings/sites', 'title' => 'Sites',
            ],
            [
                'url' => '/settings/routes', 'title' => 'Routes', 'extraContent' => [
                ['rendered' => 'No routes exist yet.'],
            ],
            ],
            [
                'url' => '/settings/fields', 'title' => 'Fields', 'extraContent' => [
                ['rendered' => 'New field'],
            ],
            ],

            [
                'url' => '/settings/assets', 'title' => 'Volumes - Asset Settings', 'extraContent' => [
                ['rendered' => 'New volume'],
                ['rendered' => 'Image Transforms'],
            ],
            ],
            [
                'url' => '/settings/assets/transforms', 'title' => 'Image Transforms - Asset Settings', 'extraContent' => [
                ['rendered' => 'New image transform'],
            ],
            ],

            // Utility pages
            [
                'url' => '/utilities', 'title' => 'System Report', 'extraContent' => [
                ['rendered' => 'Application Info'],
                ['rendered' => 'Yii version'],
                ['rendered' => 'Plugins'],
                ['rendered' => 'Requirements'],
            ],
            ],
            [
                'url' => '/utilities/updates', 'title' => 'Updates',
            ],
            [
                'url' => '/utilities/project-config', 'title' => 'Project Config', 'extraContent' => [
                ['rendered' => 'Apply YAML Changes'],
                ['rendered' => 'Rebuild the Config'],
                ['rendered' => 'Loaded Project Config Data'],
            ],
            ],
            [
                'url' => '/utilities/php-info', 'title' => 'PHP Info',
            ],
            [
                'url' => '/utilities/system-messages', 'title' => 'System Messages', 'extraContent' => [
                ['rendered' => 'When someone creates an account:'],
                ['rendered' => 'When someone changes their email address:'],
                ['rendered' => 'When someone forgets their password:'],
                ['rendered' => 'When you are testing your email settings:'],
            ],
            ],
            [
                'url' => '/utilities/queue-manager', 'title' => 'Queue Manager', 'extraContent' => [
                ['rendered' => 'No pending jobs.'],
            ],
            ],
            [
                'url' => '/utilities/deprecation-errors', 'title' => 'Deprecation Warnings', 'extraContent' => [
                ['rendered' => 'No deprecation warnings to report!'],
            ],
            ],
            [
                'url' => '/utilities/find-replace', 'title' => 'Find and Replace', 'extraContent' => [
                ['rendered' => 'Find Text'],
                ['rendered' => 'Replace Text'],
            ],
            ],
            [
                'url' => '/utilities/migrations', 'title' => 'Migrations', 'extraContent' => [
                ['rendered' => 'No pending content migrations.'],
            ],
            ],
            [
                'url' => '/utilities/clear-caches', 'title' => 'Caches', 'extraContent' => [
                ['rendered' => 'Clear Caches'],
                ['rendered' => 'Invalidate Data Caches'],
            ],
            ],
            [
                'url' => '/utilities/db-backup', 'title' => 'Database Backup', 'extraContent' => [
                ['rendered' => 'Download backup'],
            ],
            ],
        ];
    }
}
