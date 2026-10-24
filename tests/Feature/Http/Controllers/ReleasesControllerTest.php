<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Models\Element;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Release\Enums\ReleaseStatus;
use CraftCms\Cms\Release\Models\Release;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Tests\TestClasses\Release\DraftableReleaseElement;
use CraftCms\Cms\Tests\TestClasses\Release\ReleasePermissions;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->actor = User::findOne();
    $this->releases = app(Releases::class);

    actingAs($this->actor);
});

afterEach(fn () => Date::setTestNow());

it('is a Craft Pro feature', function () {
    Edition::set(Edition::Team);
    config()->set('app.debug', false);
    $release = $this->releases->create('Launch', null, $this->actor);

    get(route('craft.cp.releases.index'))->assertNotFound();
    get(route('craft.cp.releases.show', $release))->assertNotFound();

    $navLabels = collect(app(Navigation::class)->getItems())
        ->flatMap(fn (NavItem $item): array => [$item->label, ...collect($item->subnav ?: [])->pluck('label')])
        ->all();

    expect($navLabels)->not->toContain('Releases')
        ->and(app(UserPermissions::class)->getAllPermissions()->pluck('handle'))->not->toContain('releases');
});

it('stores a release with its target time in the user’s time zone', function () {
    Cms::config()->timezone = 'Asia/Tokyo';

    postJson(route('craft.cp.releases.store'), [
        'name' => '  October launch  ',
        'targetDate' => ['date' => '2026-10-16', 'time' => '06:30', 'timezone' => 'America/New_York'],
    ])->assertOk();

    $release = Release::query()->where('name', 'October launch')->sole();

    expect($release->targetDate->utc()->toDateTimeString())->toBe('2026-10-15 21:30:00');

    postJson(route('craft.cp.releases.store'), ['name' => ''])
        ->assertJsonValidationErrorFor('name');
});

it('updates release details using the resource HTTP methods', function (string $method) {
    $release = $this->releases->create('Original launch', null, $this->actor);

    $this->json($method, route('craft.cp.releases.update', $release), [
        'name' => 'Updated launch',
        'description' => 'Ready for publication.',
    ])->assertOk();

    $release->refresh();

    expect($release->name)->toBe('Updated launch')
        ->and($release->description)->toBe('Ready for publication.');
})->with(['PUT', 'PATCH']);

it('returns to the releases index after saving, unless continuing to edit', function () {
    $release = $this->releases->create('Launch', null, $this->actor);
    $props = get(route('craft.cp.releases.show', $release), headers: ['X-Inertia' => 'true'])
        ->assertOk()
        ->assertJsonPath('props.formActions.0.label', 'Save and continue editing')
        ->json('props');

    $this->json('PATCH', route('craft.cp.releases.update', $release), [
        'name' => 'Launch',
        'redirect' => $props['redirectUrl'],
    ])->assertJsonPath('redirect', route('craft.cp.releases.index'));

    $this->json('PATCH', route('craft.cp.releases.update', $release), [
        'name' => 'Launch',
        'redirect' => $props['formActions'][0]['redirect'],
    ])->assertJsonPath('redirect', route('craft.cp.releases.show', $release));
});

it('schedules a release at the submitted time in the user’s time zone and requires a time', function () {
    Cms::config()->timezone = 'America/New_York';
    Date::setTestNow('2026-10-01 12:00:00');

    $release = $this->releases->create('Scheduled launch', Date::parse('2026-10-02 08:00:00', Cms::timezone()), $this->actor);
    $draft = releaseControllerDraft($this->actor);
    $this->releases->attach($release, $draft, $this->actor);

    $modal = getJson(route('craft.cp.releases.scheduleModal', $release))
        ->assertOk()
        ->assertJsonPath('ui.values.publishAt', [
            'date' => '2026-10-02',
            'time' => '08:00',
            'timezone' => Cms::timezone(),
        ]);
    $publishAt = $modal->json('ui.values.publishAt');

    postJson(route('craft.cp.releases.schedule', $release), [
        'publishAt' => ['date' => '2026-10-02'],
    ])->assertJsonValidationErrorFor('publishAt.time');

    postJson(route('craft.cp.releases.schedule', $release), [
        'publishAt' => [...$publishAt, 'date' => '2026-09-30'],
    ])->assertJsonValidationErrors(['publishAt.date' => 'The scheduled publication time must be in the future.']);

    $publishAt['time'] = '09:00';
    $publishAt['timezone'] = 'Asia/Tokyo';
    postJson(route('craft.cp.releases.schedule', $release), ['publishAt' => $publishAt])->assertOk();

    $release->refresh();

    expect($release->status)->toBe(ReleaseStatus::Scheduled)
        ->and($release->publishedAt->utc()->toDateTimeString())->toBe('2026-10-02 13:00:00');

    get(route('craft.cp.releases.show', $release))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('readOnly', true)
            ->where('actionMenu', [])
            ->where('formActions', [])
            ->where('redirectUrl', null)
            ->where('editUi', null));

    getJson(route('craft.cp.releases.scheduleModal', $release))->assertForbidden();

    get($draft->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('editorActions.buttons.0.disabled', true)
            ->where('editorActions.buttons.0.disabledReason', 'Cancel the release schedule before applying this draft.'));
});

it('shows release item counts while restricting management to release managers', function () {
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create();
    $contributor = UserModel::factory()
        ->withPermissions(['accessCp', ...ReleasePermissions::forSection($section)])
        ->createElement();

    $release = $this->releases->create('Counted release', null, $this->actor);
    $this->releases->attach($release, releaseControllerDraft($this->actor), $this->actor);

    get(route('craft.cp.releases.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.createUrl', route('craft.cp.releases.create'))
            ->where('ui.nodes.0.props.rows.0._deletable', true)
            ->where('ui.nodes.0.props.rows.0.itemCount', 1));

    $this->flushSession();
    actingAs($contributor);

    get(route('craft.cp.releases.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.createUrl', null)
            ->where('ui.nodes.0.props.rows.0._deletable', false)
            ->where('ui.nodes.0.props.rows.0._deleteUrl', null)
            ->where('ui.nodes.0.props.rows.0.id', $release->id)
            ->where('ui.nodes.0.props.rows.0.itemCount', 1));

    get(route('craft.cp.releases.create'))->assertForbidden();
    postJson(route('craft.cp.releases.store'), ['name' => 'Contributor release'])->assertForbidden();

    expect(Release::query()->where('name', 'Contributor release')->exists())->toBeFalse();

    $manager = UserModel::factory()->withPermissions(['accessCp', 'manageReleases'])->createElement();
    $this->flushSession();
    actingAs($manager);

    getJson(route('craft.cp.releases.scheduleModal', $release))->assertForbidden();
});

it('filters, paginates, and sorts release rows through Ui reloads', function () {
    $alpha = $this->releases->create('Alpha launch', null, $this->actor);
    $beta = $this->releases->create('Beta launch', null, $this->actor);
    $this->releases->create('Other content', null, $this->actor);

    $query = ['search' => 'launch', 'per_page' => 1];
    get(route('craft.cp.releases.index', $query))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui')
            ->where('ui.nodes.0.props.pagination.total', 2)
            ->where('ui.nodes.0.props.rows.0.id', $beta->id));

    get(route('craft.cp.releases.index', [
        ...$query,
        Cms::config()->getPageTriggerParam() => 2,
    ]), headers: [
        'X-Inertia' => 'true',
        'X-Inertia-Partial-Component' => 'Ui',
        'X-Inertia-Partial-Data' => 'ui',
    ])
        ->assertOk()
        ->assertJsonPath('props.ui.nodes.0.props.pagination.current_page', 2)
        ->assertJsonPath('props.ui.nodes.0.props.rows.0.id', $alpha->id);

    get(route('craft.cp.releases.index', [
        ...$query,
        'sort' => [['field' => 'name', 'direction' => 'asc']],
    ]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.rows.0.id', $alpha->id));

    $beta->update(['status' => ReleaseStatus::Scheduled]);

    get(route('craft.cp.releases.index', ['status' => 'scheduled']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.pagination.total', 1)
            ->where('ui.nodes.0.props.rows.0.id', $beta->id));
});

it('sorts releases by their publication or target date with undated releases last', function () {
    $undated = $this->releases->create('Undated', null, $this->actor);
    $late = $this->releases->create('Late', Date::parse('2026-12-01 09:00:00'), $this->actor);
    $early = $this->releases->create('Early', Date::parse('2026-11-01 09:00:00'), $this->actor);
    $scheduled = $this->releases->create('Scheduled', Date::parse('2026-10-20 09:00:00'), $this->actor);
    $scheduled->update(['status' => ReleaseStatus::Scheduled, 'publishedAt' => Date::parse('2026-11-15 09:00:00')]);

    foreach (['asc' => [$early, $scheduled, $late, $undated], 'desc' => [$late, $scheduled, $early, $undated]] as $direction => $expected) {
        get(route('craft.cp.releases.index', ['sort' => [['field' => 'date', 'direction' => $direction]]]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('ui.nodes.0.props.rows', fn ($rows) => collect($rows)->pluck('id')->all() === array_map(fn (Release $release) => $release->id, $expected)));
    }
});

it('attaches and edits the same draft, and explicitly removes it from the release', function () {
    $draft = releaseControllerDraft($this->actor);
    $release = $this->releases->create('Attach release', null, $this->actor);

    postJson(route('craft.cp.releases.attach', $release), ['draftId' => 999999])->assertNotFound();

    postJson(route('craft.cp.releases.attach', $release), [
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
    ])
        ->assertOk()
        ->assertJsonPath('draftId', $draft->draftId)
        ->assertJsonPath('redirect', $draft->getCpEditUrl());

    expect($this->releases->releaseForDraft($draft)?->id)->toBe($release->id);

    $screen = get($draft->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('release.id', $release->id)
            ->where('readOnly', false)
            ->where('canAutosave', true));

    $action = $screen->inertiaProps('editorActions.primary');
    app()->forgetScopedInstances();
    $saveResponse = postJson($action['actionUrl'] ?? $screen->inertiaProps('applyDraftUrl'), [
        ...$action['params'],
        'elementType' => EntryElement::class,
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
        'title' => 'Edited release content',
    ]);
    $saveResponse->assertOk();

    expect(EntryElement::find()->draftId($draft->draftId)->status(null)->one()?->title)->toBe('Edited release content')
        ->and($this->releases->releaseForDraft($draft)?->id)->toBe($release->id)
        ->and(EntryElement::find()->id($draft->getCanonicalId())->status(null)->one()->title)->toBe('Controller original');

    $page = get(route('craft.cp.releases.show', $release), headers: ['X-Inertia' => 'true'])
        ->assertOk()
        ->assertJsonPath('props.readiness.ready', true);
    expect($page->json('props.ui.nodes.0.props.rows.0.title.html'))
        ->toContain(sprintf('href="%s"', Html::encode($draft->getCpEditUrl())));
    $removeUrl = $page->json('props.ui.nodes.0.props.rows.0._deleteUrl');

    deleteJson($removeUrl)->assertOk();

    get(route('craft.cp.releases.show', $release), headers: [
        'X-Inertia' => 'true',
        'X-Inertia-Partial-Component' => 'releases/Show',
        'X-Inertia-Partial-Data' => 'ui',
    ])
        ->assertOk()
        ->assertJsonPath('props.ui.nodes.0.props.rows', [])
        ->assertJsonPath('props.readiness', null)
        ->assertJsonPath('props.release.canPublish', false);

    expect($this->releases->releaseForDraft($draft))->toBeNull()
        ->and(Elements::getElementById($draft->id))->not->toBeNull();
});

it('sends release errors back to the form for non-JSON requests', function () {
    $release = $this->releases->create('Empty release', null, $this->actor);

    post(route('craft.cp.releases.publish', $release), headers: ['referer' => route('craft.cp.releases.show', $release)])
        ->assertRedirect(route('craft.cp.releases.show', $release))
        ->assertSessionHasErrors(['release' => 'The release has no drafts.']);

    getJson(route('craft.cp.releases.scheduleModal', $release))->assertForbidden();

    $release->update(['status' => ReleaseStatus::Published]);

    get(route('craft.cp.releases.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.rows.0._deletable', false)
            ->where('ui.nodes.0.props.rows.0._deleteUrl', null));

    delete(route('craft.cp.releases.destroy', $release))
        ->assertSessionHasErrors(['release' => 'Scheduled and completed releases cannot be changed.']);

    expect($release->fresh())->not()->toBeNull();
});

function releaseControllerDraft(User $actor): EntryElement
{
    $canonical = EntryModel::factory()->createElement(['title' => 'Controller original']);
    Sections::refreshSections();
    EntryTypes::refreshEntryTypes();

    $draft = app(Drafts::class)->createDraft(canonical: $canonical, creatorId: $actor->id, name: 'Controller draft');
    $draft->title = 'Controller change';

    if (! Elements::saveElement($draft)) {
        throw new RuntimeException('The release controller test draft could not be saved.');
    }

    return $draft;
}

it('shows an entry’s publication warnings in its release row', function () {
    $draft = releaseControllerDraft($this->actor);
    $release = $this->releases->create('Warned release', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);

    Date::setTestNow(now()->addMinute());
    $canonical = $draft->getCanonical();
    $canonical->title = 'Live edit';
    expect(Elements::saveElement($canonical))->toBeTrue();

    get(route('craft.cp.releases.show', $release))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.rows.0.status.html', fn (string $html) => str_contains($html, 'Ready with warnings'))
            ->where('ui.nodes.0.props.rows.0.issues.html', fn (string $html) => str_contains($html, 'Live changes will be merged into this draft before publication.')));
});

it('offers adding to a release only while editing a draft', function (Closure $editedElement, bool $canAddToRelease) {
    $draft = releaseControllerDraft($this->actor);
    $this->releases->create('Editor release', null, $this->actor);

    get($editedElement($draft, $this->actor)->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canAddToRelease', $canAddToRelease)
            ->where('releaseOptions', fn ($options) => count($options) === ($canAddToRelease ? 1 : 0)));
})->with([
    'live entry' => [fn (EntryElement $draft): EntryElement => $draft->getCanonical(), false],
    'saved draft' => [fn (EntryElement $draft): EntryElement => $draft, true],
    'provisional draft' => [function (EntryElement $draft, User $actor): EntryElement {
        app(Drafts::class)->createDraft(canonical: $draft->getCanonical(), creatorId: $actor->id, provisional: true);

        return $draft->getCanonical();
    }, true],
]);

it('attaches a non-entry draft through HTTP and displays its release item', function () {
    $model = Element::factory()->create(['type' => DraftableReleaseElement::class]);
    $canonical = DraftableReleaseElement::find()->id($model->id)->one();
    $draft = app(Drafts::class)->createDraft($canonical, $this->actor->id, 'Prepared content');
    $draft->title = 'Prepared content';
    expect(Elements::saveElement($draft))->toBeTrue();

    $release = $this->releases->create('Mixed content', null, $this->actor);
    postJson(route('craft.cp.releases.attach', $release), [
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
    ])->assertOk()->assertJsonPath('draftId', $draft->draftId);

    get(route('craft.cp.releases.show', $release))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.rows.0.title.html', fn (string $html) => str_contains($html, 'appearance="plain"')
                && str_contains($html, '<span class="context-label">Prepared content</span>')));
});

it('shows release metadata without content links or removal actions', function () {
    $draft = releaseControllerDraft($this->actor);
    $release = $this->releases->create('Restricted content', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);

    $publisher = UserModel::factory()->withPermissions(['accessCp', 'manageReleases', 'publishReleases'])->createElement();
    actingAs($publisher);

    expect($publisher->can('view', $draft))->toBeFalse();

    getJson(route('craft.cp.releases.scheduleModal', $release))->assertOk();

    get(route('craft.cp.releases.show', $release))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('release.canPublish', true)
            ->where('ui.nodes.0.props.rows.0.title.html', fn (string $html) => str_contains($html, 'Controller change')
                && str_contains($html, '<span class="context-label">Controller draft</span>')
                && ! str_contains($html, '<a '))
            ->where('ui.nodes.0.props.rows.0._deleteUrl', null)
            ->where('ui.nodes.0.props.rows.0._deletable', false));

    deleteJson(route('craft.cp.releases.detach', [$release, $draft->draftId]))->assertForbidden();

    expect($this->releases->releaseForDraft($draft)?->id)->toBe($release->id);
});

it('lists a draft whose section was deleted and lets release managers remove it', function () {
    $draft = releaseControllerDraft($this->actor);
    $release = $this->releases->create('Deleted section', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);

    // What deleting the section leaves behind: its entries and the section
    // soft-deleted, and drafts of those entries untouched.
    DB::table(Table::ELEMENTS)->where('id', $draft->getCanonicalId())->softDelete();
    DB::table(Table::SECTIONS)->softDelete($draft->sectionId);
    Sections::refreshSections();

    get(route('craft.cp.releases.show', $release))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ui.nodes.0.props.rows.0.title.html', 'Controller change<span class="context-label">Controller draft</span>')
            ->where('ui.nodes.0.props.rows.0.issues.html', fn (string $html) => str_contains($html, 'The entry has been deleted.'))
            ->where('ui.nodes.0.props.rows.0._deletable', true));

    actingAs(UserModel::factory()->withPermissions(['accessCp', 'manageReleases'])->createElement());

    deleteJson(route('craft.cp.releases.detach', [$release, $draft->draftId]))->assertOk();

    expect($this->releases->releaseForDraft($draft))->toBeNull();
});

it('lists a release’s drafts for publishing, holding publication until none are blocked', function () {
    $ready = releaseControllerDraft($this->actor);
    $blocked = releaseControllerDraft($this->actor);
    $release = $this->releases->create('Publish from the palette', null, $this->actor);
    $this->releases->attach($release, $ready, $this->actor);
    $this->releases->attach($release, $blocked, $this->actor);
    DB::table(Table::ELEMENTS)->where('id', $blocked->getCanonicalId())->softDelete();

    $modal = getJson(route('craft.cp.releases.publishModal', $release))
        ->assertOk()
        ->assertJsonPath('submitDisabled', true)
        ->assertJsonPath('submitDisabledReason', '1 draft is blocking publication.')
        ->assertJsonCount(2, 'ui.nodes.0.props.rows')
        ->assertJsonPath('ui.nodes.0.props.rows.1.status.html', fn (string $html) => str_contains($html, 'Blocked'));

    deleteJson($modal->json('ui.nodes.0.props.rows.1._deleteUrl'))->assertOk();

    getJson(route('craft.cp.releases.publishModal', $release))
        ->assertOk()
        ->assertJsonPath('submitDisabled', false)
        ->assertJsonPath('submitDisabledReason', null)
        ->assertJsonCount(1, 'ui.nodes.0.props.rows');

    deleteJson(route('craft.cp.releases.detach', [$release, $ready->draftId]))->assertOk();

    getJson(route('craft.cp.releases.publishModal', $release))
        ->assertOk()
        ->assertJsonPath('submitDisabled', true)
        ->assertJsonPath('submitDisabledReason', 'The release has no drafts.');

    $release->update(['status' => ReleaseStatus::Scheduled]);

    getJson(route('craft.cp.releases.publishModal', $release))->assertForbidden();
});

it('deletes a mutable release through its advertised action without deleting its draft', function (string $source, ReleaseStatus $status) {
    $draft = releaseControllerDraft($this->actor);
    $release = $this->releases->create('Delete release', null, $this->actor);
    $this->releases->attach($release, $draft, $this->actor);
    $release->update(['status' => $status]);

    if ($source === 'form') {
        $response = get(route('craft.cp.releases.show', $release), headers: ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.readOnly', false);
        $action = $response->json('props.actionMenu.0.action');
    } else {
        $response = get(route('craft.cp.releases.index'), headers: ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.ui.nodes.0.props.rows.0._deleteUrl', route('craft.cp.releases.destroy', $release));
        $action = ['method' => 'DELETE', 'url' => $response->json('props.ui.nodes.0.props.rows.0._deleteUrl')];
    }

    $this->json($action['method'], $action['url'])
        ->assertOk()
        ->assertJsonPath('redirect', route('craft.cp.releases.index'));

    expect($release->fresh())->toBeNull()
        ->and($this->releases->releaseForDraft($draft))->toBeNull()
        ->and(EntryElement::find()->draftId($draft->draftId)->status(null)->one()?->title)->toBe('Controller change');
})->with([
    'draft form' => ['form', ReleaseStatus::Draft],
    'failed index row' => ['index', ReleaseStatus::Failed],
]);
