<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\SitesController;
use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Models\SiteGroup;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::first());

    $this->sites = resolve(Sites::class);
    $this->siteGroups = resolve(SiteGroups::class);
});

it('requires authentication', function () {
    Auth::logout();

    get(action([SitesController::class, 'index']))->assertRedirect();
    get(action([SitesController::class, 'create']))->assertRedirect();
    get(action([SitesController::class, 'edit'], [Site::first()->id]))->assertRedirect();
    postJson(action([SitesController::class, 'store']))->assertUnauthorized();
    postJson(action([SitesController::class, 'reorder']))->assertUnauthorized();
    postJson(action([SitesController::class, 'destroy']))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    // Read only
    get(action([SitesController::class, 'edit'], [Site::first()->id]))->assertSee(t('Changes to these settings aren’t permitted in this environment.'));

    // Not allowed
    get(action([SitesController::class, 'create']))->assertForbidden();
    postJson(action([SitesController::class, 'store']))->assertForbidden();
    postJson(action([SitesController::class, 'reorder']))->assertForbidden();
    postJson(action([SitesController::class, 'destroy']))->assertForbidden();
});

test('index validates group id when passed', function () {
    get(action([SitesController::class, 'index'], ['groupId' => 999]))->assertNotFound();
});

test('index shows all sites', function () {
    $this->sites->saveSite(new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    get(action([SitesController::class, 'index']))
        ->assertSee('New site')
        ->assertSee(Site::first()->name);
});

test('index can filter by group', function () {
    $this->siteGroups->saveGroup($group = new \CraftCms\Cms\Site\Data\SiteGroup(name: 'New group'));

    $this->sites->saveSite(new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: $group->id,
    ));

    get(action([SitesController::class, 'index'], ['groupId' => Site::first()->groupId]))
        ->assertDontSee('<td data-title="Handle"><code>new-site</code></td>', false)
        ->assertSee('<td data-title="Handle"><code>'.Site::first()->handle.'</code></td>', false);
});

test('create can be loaded', function () {
    get(action([SitesController::class, 'create']))
        ->assertOk()
        ->assertSee(t('Create a new site'));
});

test('create errors if no groups exist', function () {
    SiteGroup::query()->delete();

    $this->siteGroups->refreshGroups();

    get(action([SitesController::class, 'create']))->assertServerError();
});

test('create validates groupId when passed', function () {
    get(action([SitesController::class, 'create'], ['groupId' => 999]))->assertNotFound();
});

test('it can edit a site', function () {
    $site = $this->sites->getSiteById(Site::first()->id);

    get(action([SitesController::class, 'edit'], [$site->id]))
        ->assertOk()
        ->assertSee($site->getName())
        ->assertSee($site->getLanguage(false))
        ->assertSee($site->getBaseUrl(false));
});

it('404s when a site does not exist', function () {
    get(action([SitesController::class, 'edit'], [999]))
        ->assertNotFound();
});

it('can save a site', function () {
    expect(Site::count())->toBe(1);

    post(action([SitesController::class, 'store']), [
        'name' => 'A new site',
        'handle' => 'a_new_site',
        'language' => 'en-US',
        'group' => SiteGroup::first()->id,
    ])->assertRedirectBack();

    expect(Site::count())->toBe(2);
});

test('name is required', function () {
    post(action([SitesController::class, 'store']), [
        'handle' => 'a_new_site',
        'language' => 'en-US',
        'group' => SiteGroup::first()->id,
    ])->assertSessionHasErrors('name');
});

test('handle is required', function () {
    post(action([SitesController::class, 'store']), [
        'name' => 'A new site',
        'language' => 'en-US',
        'group' => SiteGroup::first()->id,
    ])->assertSessionHasErrors('handle');
});

test('handle needs to be unique', function () {
    Site::factory()->create([
        'handle' => 'a_new_site',
    ]);

    post(action([SitesController::class, 'store']), [
        'name' => 'A new site',
        'handle' => 'a_new_site',
        'language' => 'en-US',
        'group' => SiteGroup::first()->id,
    ])->assertSessionHasErrors('handle');
});

test('handle can be duplicate if trashed', function () {
    Site::factory()->create([
        'handle' => 'a_new_site',
        'dateDeleted' => now(),
    ]);

    post(action([SitesController::class, 'store']), [
        'name' => 'A new site',
        'handle' => 'a_new_site',
        'language' => 'en-US',
        'group' => SiteGroup::first()->id,
    ])->assertSessionHasNoErrors();
});

test('language is required', function () {
    post(action([SitesController::class, 'store']), [
        'name' => 'A new site',
        'handle' => 'a_new_site',
        'group' => SiteGroup::first()->id,
    ])->assertSessionHasErrors('language');
});

test('group is required', function () {
    post(action([SitesController::class, 'store']), [
        'name' => 'A new site',
        'handle' => 'a_new_site',
        'language' => 'en-US',
    ])->assertSessionHasErrors('group');
});

it('can reorder sites', function () {
    $this->sites->saveSite($newSite = new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    ProjectConfig::rebuild();

    $defaultSite = Site::first();

    expect($newSite->sortOrder)->toBe(2);
    expect($defaultSite->sortOrder)->toBe(1);

    postJson(action([SitesController::class, 'reorder']), [
        'ids' => Json::encode([
            $newSite->id,
            Site::first()->id,
        ]),
    ])->assertOk();

    expect(Site::findOrFail($newSite->id)->sortOrder)->toBe(1);
    expect($defaultSite->fresh()->sortOrder)->toBe(2);
});

it('can delete a site', function () {
    $this->sites->saveSite($newSite = new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    expect(Site::count())->toBe(2);

    postJson(action([SitesController::class, 'destroy']), [
        'id' => $newSite->id,
    ])->assertOk();

    expect(Site::count())->toBe(1);
});
