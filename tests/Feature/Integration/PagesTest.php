<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

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
]);

it('renders inertia pages', function (string $url, string $component, string $title) {
    $response = get("/{$this->cpTrigger}{$url}");

    if ($response->status() === 404) {
        $this->markTestIncomplete('Page not found: '.$url);
    }

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component($component)
        ->where('title', $title));
})->with([
    [
        'url' => '/settings/sections',
        'component' => 'SettingsSectionsIndexPage',
        'title' => 'Sections',
    ],
]);

it('renders utility pages', function (string $url, string $title, array $extraContent = []) {
    $response = get("/{$this->cpTrigger}{$url}");

    if ($response->status() === 404) {
        $this->markTestIncomplete('Page not found: '.$url);
    }

    $response->assertInertia(function (AssertableInertia $page) use ($title, $extraContent) {
        $page->where('title', $title);
        foreach ($extraContent as $content) {
            $page->where('contentHtml', fn (string $value) => Str::contains($value, $content['rendered']));
        }
        $page->etc();
    });
})->with([
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
            ['rendered' => '<ProjectConfig'],
        ],
    ],
    ['url' => '/utilities/php-info', 'title' => 'PHP Info'],
    [
        'url' => '/utilities/queue-manager',
        'title' => 'Queue Manager',
        'extraContent' => [
            ['rendered' => '<QueueManager'],
        ],
    ],
    [
        'url' => '/utilities/deprecation-errors',
        'title' => 'Deprecation Warnings',
        'extraContent' => [
            ['rendered' => '<DeprecationErrors'],
        ],
    ],
    [
        'url' => '/utilities/find-replace',
        'title' => 'Find and Replace',
        'extraContent' => [
            ['rendered' => '<FindReplace'],
        ],
    ],
    [
        'url' => '/utilities/migrations',
        'title' => 'Migrations',
        'extraContent' => [
            ['rendered' => '<Migrations'],
        ],
    ],
    [
        'url' => '/utilities/clear-caches',
        'title' => 'Caches',
        'extraContent' => [
            ['rendered' => '<ClearCaches'],
        ],
    ],
    [
        'url' => '/utilities/db-backup',
        'title' => 'Database Backup',
        'extraContent' => [
            ['rendered' => '<DatabaseBackup'],
        ],
    ],
    [
        'url' => '/utilities/system-messages',
        'title' => 'System Messages',
        'extraContent' => [
            ['rendered' => '<SystemMessages'],
        ],
    ],
]);
