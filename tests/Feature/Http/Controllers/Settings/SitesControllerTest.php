<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Http\Controllers\Settings\SitesController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Models\SiteGroup;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->sites = app(Sites::class);
    $this->siteGroups = app(SiteGroups::class);
});

it('requires authentication', function () {
    Auth::logout();

    get(action([SitesController::class, 'index']))->assertRedirect();
    get(action([SitesController::class, 'create']))->assertRedirect();
    get(action([SitesController::class, 'edit'], [Site::first()->id]))->assertRedirect();
    postJson(action([SitesController::class, 'renderUi']))->assertUnauthorized();
    postJson(action([SitesController::class, 'store']))->assertUnauthorized();
    postJson(action([SitesController::class, 'reorder']))->assertUnauthorized();
    deleteJson(action([SitesController::class, 'destroy'], [Site::first()->id]))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([SitesController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.createUrl', null)
            ->where('ui.nodes.0.props.deletable', false)
            ->where('ui.nodes.0.props.reorderUrl', null)
            ->missing('ui.nodes.0.props.rows.0._deleteUrl')
            ->where('ui.nodes.0.props.rows.0.name.url', route('craft.cp.settings.sites.edit', ['site' => Site::first()->id])));

    $this->get(action([SitesController::class, 'edit'], [Site::first()->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/sites/Edit')
            ->where('ui.nodes', fn ($nodes): bool => collect(flattenUiNodes(collect($nodes)->all()))
                ->whereNotNull('control')
                ->every(fn (array $node): bool => $node['control']['mode'] === 'readOnly')));

    // Not allowed
    get(action([SitesController::class, 'create']))->assertForbidden();
    postJson(action([SitesController::class, 'store']))->assertForbidden();
    postJson(action([SitesController::class, 'renderUi']))->assertForbidden();
    postJson(action([SitesController::class, 'reorder']))->assertForbidden();
    deleteJson(action([SitesController::class, 'destroy'], [Site::first()->id]))->assertForbidden();
});

test('index validates group id when passed', function () {
    get(action([SitesController::class, 'index'], ['groupId' => 999]))->assertNotFound();
});

test('index shows all sites', function () {
    $this->sites->saveSite($newSite = new SiteData([
        'name' => 'New site',
        'handle' => 'newSite',
        'language' => 'nl',
        'enabled' => false,
        'baseUrl' => 'https://example.com/?a=1&b=2',
        'groupId' => SiteGroup::first()->id,
    ]));

    $this->get(action([SitesController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/sites/Index')
            ->has('ui.nodes.0.props.rows', 2)
            ->where('ui.nodes.0.props.rows.0.id', Site::first()->id)
            ->where('ui.nodes.0.props.rows.0.primary.label', 'Yes')
            ->where('ui.nodes.0.props.rows.0._deletable', false)
            ->where('ui.nodes.0.props.rows.0._deleteDisabledReason', 'You cannot delete the primary site.')
            ->where('ui.nodes.0.props.rows.1.id', $newSite->id)
            ->where('ui.nodes.0.props.rows.1.name.label', 'New site')
            ->where('ui.nodes.0.props.rows.1.name.url', route('craft.cp.settings.sites.edit', ['site' => $newSite->id]))
            ->where('ui.nodes.0.props.rows.1.handle.html', fn (string $html): bool => new Crawler($html)->filter('code')->text() === 'newSite')
            ->where('ui.nodes.0.props.rows.1.language.html', fn (string $html): bool => new Crawler($html)->filter('code')->text() === 'nl')
            ->where('ui.nodes.0.props.rows.1.status.html', fn (string $html): bool => new Crawler($html)->filter('craft-badge')->text() === 'Disabled')
            ->where('ui.nodes.0.props.rows.1.baseUrl.html', fn (string $html): bool => new Crawler($html)->filter('code')->text() === 'https://example.com/?a=1&b=2/')
            ->where('ui.nodes.0.props.rows.1.group', SiteGroup::first()->name)
            ->where('ui.nodes.0.props.rows.1.primary', null)
            ->where('ui.nodes.0.props.rows.1._deletable', true)
            ->where('ui.nodes.0.props.rows.1._deleteUrl', route('craft.cp.settings.sites.destroy', ['site' => $newSite->id]))
            ->where('ui.nodes.0.props.reorderUrl', null)
        );
});

test('index can filter by group', function () {
    $this->siteGroups->saveGroup($group = new CraftCms\Cms\Site\Data\SiteGroup(['name' => 'New group']));

    $this->sites->saveSite($newSite = new SiteData([
        'name' => 'New site',
        'handle' => 'newSite',
        'language' => 'nl',
        'groupId' => $group->id,
    ]));

    $response = $this->get(action([SitesController::class, 'index'], ['groupId' => $group->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/sites/Index')
            ->has('ui.nodes.0.props.rows', 1)
            ->where('ui.nodes.0.props.rows.0.id', $newSite->id)
            ->where('ui.nodes.0.props.reorderUrl', route('craft.cp.settings.sites.reorder'))
            ->where('ui.nodes.0.props.createUrl', route('craft.cp.settings.sites.create', ['groupId' => $group->id]))
            ->has('group', fn (AssertableInertia $page) => $page
                ->where('id', $group->id)
                ->where('name', $group->name)
                ->etc()
            )
        );

    $navigation = collect($response->inertiaProps('subnav'))->firstWhere('label', 'New group');
    parse_str(parse_url($navigation['href'], PHP_URL_QUERY), $query);

    expect($navigation['selected'])->toBeTrue()
        ->and(parse_url($navigation['href'], PHP_URL_PATH))->toBe(route('craft.cp.settings.sites.index', absolute: false))
        ->and($query['groupId'])->toBe((string) $group->id);
});

test('create can be loaded', function () {
    get(action([SitesController::class, 'create']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/sites/Edit')
            ->where('ui.values.siteId', null)
            ->where('ui.values.group', SiteGroup::first()->id)
            ->where('ui.refreshable', true)
            ->where('submit.url', action([SitesController::class, 'store']))
            ->where('refreshUrl', action([SitesController::class, 'renderUi'])))
        ->assertOk();
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/sites/Edit')
            ->where('ui.values.siteId', $site->id)
            ->where('ui.values.name', $site->getName(false))
            ->where('ui.values.language', $site->getLanguage(false))
            ->where('ui.values.baseUrl', $site->getBaseUrl(false))
            ->where('ui.values.primary', true)
        );
});

it('refreshes base URL visibility from current form values', function () {
    $values = [
        'siteId' => null,
        'group' => SiteGroup::first()->id,
        'name' => 'New site',
        'handle' => 'newSite',
        'language' => 'en-US',
        'enabled' => '1',
        'primary' => false,
        'hasUrls' => false,
        'baseUrl' => '',
    ];

    $withoutBaseUrl = postJson(action([SitesController::class, 'renderUi']), [
        'values' => $values,
        'scope' => [],
    ])->json('ui.nodes');

    $withBaseUrl = postJson(action([SitesController::class, 'renderUi']), [
        'values' => [...$values, 'hasUrls' => true],
        'scope' => [],
    ])->json('ui.nodes');

    expect(collect(flattenUiNodes($withoutBaseUrl))->pluck('control.path'))->not->toContain(['baseUrl'])
        ->and(collect(flattenUiNodes($withBaseUrl))->pluck('control.path'))->toContain(['baseUrl']);
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
    ])->assertRedirect(route('craft.cp.settings.sites.index'));

    expect(Site::count())->toBe(2);
});

it('uses the hidden site ID to update the existing site', function () {
    $site = Site::first();

    post(action([SitesController::class, 'store']), [
        'siteId' => $site->id,
        'name' => 'Updated site',
        'handle' => $site->handle,
        'language' => $site->language,
        'group' => $site->groupId,
        'hasUrls' => $site->hasUrls,
        'baseUrl' => $site->baseUrl,
    ])->assertSessionHasNoErrors();

    expect(Site::count())->toBe(1)
        ->and(Site::findOrFail($site->id)->name)->toBe('Updated site');
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

it('can reorder sites', function (bool $json) {
    $this->sites->saveSite($newSite = new SiteData([
        'name' => 'New site',
        'handle' => 'newSite',
        'language' => 'nl',
        'groupId' => SiteGroup::first()->id,
    ]));

    ProjectConfig::rebuild();

    $defaultSite = Site::first();

    expect($newSite->sortOrder)->toBe(2);
    expect($defaultSite->sortOrder)->toBe(1);

    $response = ($json ? postJson(...) : post(...))(action([SitesController::class, 'reorder']), [
        'ids' => [$newSite->id, $defaultSite->id],
    ]);

    if ($json) {
        $response->assertOk()->assertJsonPath('message', 'New order saved.');
    } else {
        $response->assertRedirectBack();
    }

    expect(Site::findOrFail($newSite->id)->sortOrder)->toBe(1);
    expect($defaultSite->fresh()->sortOrder)->toBe(2);
})->with(['JSON' => [true], 'Form redirect' => [false]]);

it('validates site deletion intent', function (array $input, string $field) {
    $this->sites->saveSite($newSite = new SiteData([
        'name' => 'New site',
        'handle' => 'newSite',
        'language' => 'nl',
        'groupId' => SiteGroup::first()->id,
    ]));

    expect(Site::count())->toBe(2);

    deleteJson(action([SitesController::class, 'destroy'], [$newSite->id]), [
        'id' => $newSite->id,
        ...$input,
    ])->assertInvalid([$field]);

    expect(Site::count())->toBe(2);
})->with([
    'missing destination' => [['contentDestination' => 'transfer'], 'transferContentTo'],
    'null destination' => [['contentDestination' => 'transfer', 'transferContentTo' => null], 'transferContentTo'],
    'nonexistent destination' => [['contentDestination' => 'transfer', 'transferContentTo' => 999999], 'transferContentTo'],
    'noninteger destination' => [['contentDestination' => 'transfer', 'transferContentTo' => 'invalid'], 'transferContentTo'],
    'array destination' => [['contentDestination' => 'transfer', 'transferContentTo' => [1]], 'transferContentTo'],
    'missing mode' => [[], 'contentDestination'],
    'invalid mode' => [['contentDestination' => 'invalid'], 'contentDestination'],
]);

it('can delete a site', function (string $mode, string $destination, bool $primary = false) {
    $this->sites->saveSite($newSite = new SiteData([
        'name' => 'New site',
        'handle' => 'newSite',
        'language' => 'nl',
        'groupId' => SiteGroup::first()->id,
    ]));

    [$siteToDelete, $targetSite] = $primary
        ? [Site::firstOrFail(), $newSite]
        : [$newSite, Site::firstOrFail()];
    $section = Section::factory()->create();
    $section->siteSettings()->update(['siteId' => $siteToDelete->id]);
    $entry = Entry::factory()->forSection($section)->create();
    $entry->element->siteSettings()->update(['siteId' => $siteToDelete->id]);

    ProjectConfig::rebuild();

    $input = ['id' => $siteToDelete->id, 'contentDestination' => $mode];

    if ($destination !== 'missing') {
        $input['transferContentTo'] = $destination === 'valid' ? (string) $targetSite->id : ['invalid'];
    }

    $response = deleteJson(action([SitesController::class, 'destroy'], [$siteToDelete->id]), $input);

    if ($primary) {
        $response->assertServerError()->assertJsonPath('message', 'You cannot delete the primary site.');
    } else {
        $response->assertRedirect(route('craft.cp.settings.sites.index'));
    }

    $deletesContent = ! $primary && $mode === 'delete';
    $expectedSiteId = ! $primary && $mode === 'transfer' ? $targetSite->id : $siteToDelete->id;

    expect(Site::count())->toBe($primary ? 2 : 1);
    expect($entry->element->fresh()->trashed())->toBe($deletesContent);
    expect($section->fresh()->trashed())->toBe($deletesContent);
    expect($section->siteSettings()->pluck('siteId')->all())->toBe([$expectedSiteId]);
    expect($entry->element->siteSettings()->pluck('siteId')->all())->toBe([$expectedSiteId]);
})->with([
    'primary site delete' => ['delete', 'missing', true],
    'primary site transfer' => ['transfer', 'valid', true],
    'transfer' => ['transfer', 'valid'],
    'delete without destination' => ['delete', 'missing'],
    'delete with stale destination' => ['delete', 'valid'],
    'delete with malformed destination' => ['delete', 'invalid'],
]);
