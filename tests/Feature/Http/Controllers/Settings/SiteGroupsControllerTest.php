<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController;
use CraftCms\Cms\Site\Models\SiteGroup;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->sites = app(Sites::class);
    $this->siteGroups = app(SiteGroups::class);
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action([SiteGroupsController::class, 'store']))->assertUnauthorized();
    deleteJson(action([SiteGroupsController::class, 'destroy'], ['groupId' => 1]))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([SiteGroupsController::class, 'store']))->assertForbidden();
    deleteJson(action([SiteGroupsController::class, 'destroy'], ['groupId' => 1]))->assertForbidden();
});

it('can save a site group', function () {
    $response = postJson(action([SiteGroupsController::class, 'store']), [
        'name' => 'A new group',
    ]);

    $created = SiteGroup::latest('id')->first();
    expect(SiteGroup::count())->toBe(2);
    expect($created->name)->toBe('A new group');
    $response->assertRedirect(route('craft.cp.settings.sites.index', ['groupId' => $created->id]));
});

it('can save an existing group', function () {
    $siteGroup = $this->siteGroups->getGroupById(SiteGroup::first()->id);

    postJson(action([SiteGroupsController::class, 'store']), [
        'id' => $siteGroup->id,
        'uid' => $siteGroup->uid,
        'name' => 'Updated group',
    ])->assertRedirect(route('craft.cp.settings.sites.index', ['groupId' => $siteGroup->id]));

    expect(SiteGroup::count())->toBe(1);
    expect(SiteGroup::first()->name)->toBe('Updated group');
});

it('can delete a site group', function () {
    $response = postJson(action([SiteGroupsController::class, 'store']), [
        'name' => 'A new group',
    ]);

    $created = SiteGroup::latest('id')->first();
    $response->assertRedirect(route('craft.cp.settings.sites.index', ['groupId' => $created->id]));

    expect(SiteGroup::count())->toBe(2);

    deleteJson(action([SiteGroupsController::class, 'destroy'], ['groupId' => $created->id]), [
        'id' => SiteGroup::latest('id')->first()->id,
    ])->assertRedirect(route('craft.cp.settings.sites.index'));

    expect(SiteGroup::count())->toBe(1);
});
