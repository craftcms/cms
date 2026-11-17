<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::first());
    $this->withoutExceptionHandling();
});

it('renders control panel pages', function (string $path, string $heading) {
    assertNoJavaScriptErrors(visitCp($path))
        ->assertSeeIn('#main h1', $heading);
})->with([
    'dashboard' => ['dashboard', 'Dashboard'],
    'entries' => ['content/entries', 'All entries'],
    'assets' => ['assets', 'Temporary Uploads'],
    'users' => ['users', 'Users'],
    'new user' => ['users/new', 'New user'],
    'my account' => ['myaccount', 'My Account'],
    'preferences' => ['myaccount/preferences', 'Preferences'],
    'releases' => ['releases', 'Releases'],
    'system report' => ['utilities/system-report', 'System Report'],
    'project config' => ['utilities/project-config', 'Project Config'],
    'caches' => ['utilities/clear-caches', 'Caches'],
    'queue manager' => ['utilities/queue-manager', 'Queue Manager'],
    'graphql schemas' => ['graphql/schemas', 'GraphQL Schemas'],
    'graphql tokens' => ['graphql/tokens', 'GraphQL Tokens'],
    'settings' => ['settings', 'Settings'],
    'general settings' => ['settings/general', 'General Settings'],
    'sites' => ['settings/sites', 'Sites'],
    'new site' => ['settings/sites/new', 'Create a new site'],
    'routes' => ['settings/routes', 'Routes'],
    'user settings' => ['settings/users', 'User Settings'],
    'cp navigation' => ['settings/cp-nav', 'CP Navigation'],
    'addresses' => ['settings/addresses', 'Address Fields'],
    'email' => ['settings/email', 'Email Settings'],
    'sections' => ['settings/sections', 'Sections'],
    'new section' => ['settings/sections/new', 'Create a new section'],
    'entry types' => ['settings/entry-types', 'Entry Types'],
    'new entry type' => ['settings/entry-types/new', 'Create a new entry type'],
    'workflows' => ['settings/workflows', 'Workflows'],
    'volumes' => ['settings/assets', 'Volume Settings'],
    'new volume' => ['settings/assets/volumes/new', 'Create a new asset volume'],
    'image transforms' => ['settings/assets/transforms', 'Image Transforms'],
    'fields' => ['settings/fields', 'Fields'],
    'new field' => ['settings/fields/new', 'Create a new field'],
    'plugins' => ['settings/plugins', 'Plugins'],
    'mcp' => ['settings/mcp', 'MCP'],
]);
