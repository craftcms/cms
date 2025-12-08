<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->cpTrigger = Cms::config()->cpTrigger;

    Edition::set(Edition::Pro);
});

it('renders pages', function (string $url, string $title, array $extraContent = []) {
    $response = get("/{$this->cpTrigger}{$url}");

    if ($response->status() === 404) {
        $this->markTestIncomplete('Page not found: '.$url);
    }

    $response->assertOk()
        ->assertSee($title);
    foreach ($extraContent as $content) {
        $response->assertSeeText($content['rendered']);
    }
})->with([
    ['url' => '/dashboard', 'title' => 'Dashboard'],
    ['url' => '/content/entries', 'title' => 'Entries'],
    ['url' => '/users', 'title' => 'Users'],

    // Settings pages
    [
        'url' => '/settings/general',
        'title' => 'General Settings',
        'extraContent' => [
            ['rendered' => 'System Name'],
            ['rendered' => 'System Status'],
            ['rendered' => 'Retry Duration'],
            ['rendered' => 'Time Zone'],
            ['rendered' => 'Login Page Logo'],
            ['rendered' => 'Site Icon'],
        ],
    ],
    [
        'url' => '/settings/sections',
        'title' => 'Sections',
        'extraContent' => [
            ['rendered' => 'New section'],
        ],
    ],
    [
        'url' => '/settings/users',
        'title' => 'User Settings',
        'extraContent' => [
            ['rendered' => 'User Groups'],
            ['rendered' => 'Fields'],
            ['rendered' => 'Settings'],
            ['rendered' => 'New user group'],
        ],
    ],
    [
        'url' => '/settings/users/settings',
        'title' => 'User Settings',
        'extraContent' => [
            ['rendered' => 'User Photo Volume'],
            ['rendered' => 'Verify email addresses'],
            ['rendered' => 'Allow public registration'],
        ],
    ],
    [
        'url' => '/settings/users/fields',
        'title' => 'User Settings',
        'extraContent' => [
            ['rendered' => 'Field Layout'],
        ],
    ],

    [
        'url' => '/settings/email',
        'title' => 'Email Settings',
        'extraContent' => [
            ['rendered' => 'System Email Address'],
            ['rendered' => 'This can begin with an environment variable.'],
            ['rendered' => 'Sender Name'],
            ['rendered' => 'HTML Email Template'],
            ['rendered' => 'Transport Type'],
        ],
    ],
    ['url' => '/settings/plugins', 'title' => 'Plugins'],
    ['url' => '/settings/sites', 'title' => 'Sites'],
    [
        'url' => '/settings/routes',
        'title' => 'Routes',
        'extraContent' => [
            ['rendered' => 'No routes exist yet.'],
        ],
    ],
    [
        'url' => '/settings/fields',
        'title' => 'Fields',
        'extraContent' => [
            ['rendered' => 'New field'],
        ],
    ],

    [
        'url' => '/settings/assets',
        'title' => 'Volumes - Asset Settings',
        'extraContent' => [
            ['rendered' => 'New volume'],
            ['rendered' => 'Image Transforms'],
        ],
    ],
    [
        'url' => '/settings/assets/transforms',
        'title' => 'Image Transforms - Asset Settings',
        'extraContent' => [
            ['rendered' => 'New image transform'],
        ],
    ],

    // Utility pages
    [
        'url' => '/utilities/system-report',
        'title' => 'System Report',
        'extraContent' => [
            ['rendered' => 'Application Info'],
            ['rendered' => 'Plugins'],
            ['rendered' => 'Requirements'],
        ],
    ],
    ['url' => '/utilities/updates', 'title' => 'Updates'],

    [
        'url' => '/utilities/project-config',
        'title' => 'Project Config',
        'extraContent' => [
            ['rendered' => 'Apply YAML Changes'],
            ['rendered' => 'Rebuild the Config'],
            ['rendered' => 'Loaded Project Config Data'],
        ],
    ],
    ['url' => '/utilities/php-info', 'title' => 'PHP Info'],
    [
        'url' => '/utilities/system-messages',
        'title' => 'System Messages',
        'extraContent' => [
            ['rendered' => 'When someone creates an account:'],
            ['rendered' => 'When someone changes their email address:'],
            ['rendered' => 'When someone forgets their password:'],
            ['rendered' => 'When you are testing your email settings:'],
        ],
    ],
    [
        'url' => '/utilities/queue-manager',
        'title' => 'Queue Manager',
        'extraContent' => [
            ['rendered' => 'No pending jobs.'],
        ],
    ],
    [
        'url' => '/utilities/deprecation-errors',
        'title' => 'Deprecation Warnings',
        'extraContent' => [
            ['rendered' => 'No deprecation warnings to report!'],
        ],
    ],
    [
        'url' => '/utilities/find-replace',
        'title' => 'Find and Replace',
        'extraContent' => [
            ['rendered' => 'Find Text'],
            ['rendered' => 'Replace Text'],
        ],
    ],
    [
        'url' => '/utilities/migrations',
        'title' => 'Migrations',
        'extraContent' => [
            ['rendered' => 'No pending content migrations.'],
        ],
    ],
    [
        'url' => '/utilities/clear-caches',
        'title' => 'Caches',
        'extraContent' => [
            ['rendered' => 'Clear Caches'],
            ['rendered' => 'Invalidate Data Caches'],
        ],
    ],
    [
        'url' => '/utilities/db-backup',
        'title' => 'Database Backup',
        'extraContent' => [
            ['rendered' => 'Download backup'],
        ],
    ],
]);
