<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController;
use CraftCms\Cms\Site\Models\SiteGroup;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::first());

    $this->sites = resolve(Sites::class);
    $this->siteGroups = resolve(SiteGroups::class);
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action([SiteGroupsController::class, 'showGroupRenameField']))->assertUnauthorized();
    postJson(action([SiteGroupsController::class, 'store']))->assertUnauthorized();
    postJson(action([SiteGroupsController::class, 'destroy']))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([SiteGroupsController::class, 'showGroupRenameField']))->assertForbidden();
    postJson(action([SiteGroupsController::class, 'store']))->assertForbidden();
    postJson(action([SiteGroupsController::class, 'destroy']))->assertForbidden();
});

it('can show the group rename field', function () {
    postJson(action([SiteGroupsController::class, 'showGroupRenameField']))
        ->assertOk()
        ->assertJsonStructure([
            'html',
            'js',
        ])
        ->assertSee('autosuggest');
});

it('can save a site group', function () {
    postJson(action([SiteGroupsController::class, 'store']), [
        'name' => 'A new group',
    ])->assertOk();

    expect(SiteGroup::count())->toBe(2);
    expect(SiteGroup::latest('id')->first()->name)->toBe('A new group');
});

it('can save an existing group', function () {
    $siteGroup = $this->siteGroups->getGroupById(SiteGroup::first()->id);

    postJson(action([SiteGroupsController::class, 'store']), [
        'id' => $siteGroup->id,
        'uid' => $siteGroup->uid,
        'name' => 'Updated group',
    ])->assertOk();

    expect(SiteGroup::count())->toBe(1);
    expect(SiteGroup::first()->name)->toBe('Updated group');
});

it('can delete a site group', function () {
    postJson(action([SiteGroupsController::class, 'store']), [
        'name' => 'A new group',
    ])->assertOk();

    expect(SiteGroup::count())->toBe(2);

    postJson(action([SiteGroupsController::class, 'destroy']), [
        'id' => SiteGroup::latest('id')->first()->id,
    ])->assertOk();

    expect(SiteGroup::count())->toBe(1);
});
