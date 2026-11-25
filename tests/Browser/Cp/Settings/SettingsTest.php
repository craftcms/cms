<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\Workflow\Models\Workflow;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::first()));

it('groups the settings', function () {
    visitCp('settings')
        ->assertSeeIn('#main >> role=heading[level=2] >> nth=0', 'System')
        ->assertSeeIn('#main >> role=heading[level=2] >> nth=1', 'Content')
        ->assertSeeIn('#main >> role=heading[level=2] >> nth=2', 'Extend');
});

it('links to each settings page', function (string $item, string $heading) {
    visitCp('settings')
        ->click("#main >> role=link[name=\"$item - Settings\"]")
        ->assertSeeIn('#main h1', $heading);
})->with([
    'General' => ['General', 'General Settings'],
    'Sites' => ['Sites', 'Sites'],
    'Routes' => ['Routes', 'Routes'],
    'Sections' => ['Sections', 'Sections'],
    'Fields' => ['Fields', 'Fields'],
]);

describe('with the workbench content', function () {
    beforeEach(fn () => seedWorkbenchContent());

    it('renders the settings pages', function (string $path, string $heading) {
        assertCpPageRenders($path, $heading);
    })->with([
        'settings' => ['settings', 'Settings'],
        'general' => ['settings/general', 'General Settings'],
        'ai connections' => ['settings/ai', 'AI Connections'],
        'sites' => ['settings/sites', 'Sites'],
        'new site' => ['settings/sites/new', 'Create a new site'],
        'site' => [fn () => 'settings/sites/'.Sites::getPrimarySite()->id, 'Craft test site'],
        'routes' => ['settings/routes', 'Routes'],
        'new route' => ['settings/routes/new', 'Create a new route'],
        'route' => [fn () => 'settings/routes/'.array_key_first(app(ProjectConfig::class)->get('routes')), 'Edit Route'],
        'users' => ['settings/users', 'User Settings'],
        'user fields' => ['settings/users/fields', 'User Settings'],
        'user account settings' => ['settings/users/settings', 'User Settings'],
        'new user group' => ['settings/users/groups/new', 'Create a new user group'],
        'user group' => [fn () => 'settings/users/groups/'.UserGroups::getGroupByHandle('reviewers')->id, 'Reviewers'],
        'cp navigation' => ['settings/cp-nav', 'CP Navigation'],
        'addresses' => ['settings/addresses', 'Address Fields'],
        'email' => ['settings/email', 'Email Settings'],
        'workflows' => ['settings/workflows', 'Workflows'],
        'new workflow' => ['settings/workflows/new', 'Create a new workflow'],
        'workflow' => [fn () => 'settings/workflows/'.Workflow::query()->value('id'), 'Editorial workflow'],
        'volumes' => ['settings/assets', 'Volume Settings'],
        'new volume' => ['settings/assets/volumes/new', 'Create a new asset volume'],
        'volume' => [fn () => 'settings/assets/volumes/'.Volumes::getVolumeByHandle('images')->id, 'Images'],
        'image transforms' => ['settings/assets/transforms', 'Image Transforms'],
        'new image transform' => ['settings/assets/transforms/new', 'Create a new image transform'],
        'image transform' => ['settings/assets/transforms/card', 'Card'],
        'plugins' => ['settings/plugins', 'Plugins'],
        'mcp' => ['settings/mcp', 'MCP'],
    ]);
});
