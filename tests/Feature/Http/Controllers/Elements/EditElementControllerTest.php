<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Http\Controllers\Elements\EditElementController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

dataset('editElementEntryRoutes', [
    'entries route' => [
        fn (Entry $entry) => cp_url(sprintf(
            'entries/%s/%d-%s',
            $entry->getSection()->handle,
            $entry->id,
            $entry->slug,
        )),
    ],
    'content route' => [
        fn (Entry $entry) => cp_url(sprintf(
            'content/entries/%s/%d-%s',
            $entry->getSection()->handle,
            $entry->id,
            $entry->slug,
        )),
    ],
    'entries route without slug' => [
        fn (Entry $entry) => cp_url(sprintf(
            'entries/%s/%d',
            $entry->getSection()->handle,
            $entry->id,
        )),
    ],
    'content route without slug' => [
        fn (Entry $entry) => cp_url(sprintf(
            'content/entries/%s/%d',
            $entry->getSection()->handle,
            $entry->id,
        )),
    ],
]);

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.edit-element-controller-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/edit-element-controller-test'),
    ]);

    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new EntryTitleField(['uid' => 'entry-title'])));
    $config = $layout->getConfig();
    $config['tabs'][0]['uid'] = 'entry-content';
    $layout = FieldLayoutModel::factory()->create(['type' => Entry::class, 'config' => $config]);
    $this->entryType = EntryType::factory()->create(['fieldLayoutId' => $layout->id]);
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'news',
        'enableVersioning' => true,
    ]);
    $this->volume = Volume::factory()->create(['fs' => 'disk:edit-element-controller-test']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

it('requires login for each entry control panel edit route', function (Closure $route) {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    Auth::logout();

    get($route($entry))->assertRedirectContains('login');
})->with('editElementEntryRoutes');

it('requires login for the asset control panel edit route', function () {
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
    ]);

    Auth::logout();

    get($asset->getCpEditUrl())->assertRedirectContains('login');
});

it('requires authentication for the action route', function () {
    Auth::logout();

    postJson(action(EditElementController::class), [
        'elementType' => Entry::class,
    ], [
        'X-Craft-Container-Id' => 'slideout',
    ])->assertUnauthorized();
});

it('renders the current entry edit screen for each control panel route', function (Closure $route) {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    get($route($entry))
        ->assertOk()
        ->assertSeeText('Current Title')
        ->assertSee('data-form-tab="entry-content"', false)
        ->assertSeeText('Create a draft')
        ->assertSee('elements/save', false);
})->with('editElementEntryRoutes');

it('renders the asset edit screen', function () {
    $this->withoutExceptionHandling();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'featured-image.jpg',
    ]);

    get($asset->getCpEditUrl())
        ->assertOk()
        ->assertSee(sprintf('"elementId":%d', $asset->id), false);
});

it('returns responses resolved by the element request', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    post(action(EditElementController::class), [
        'elementType' => $entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'draftId' => 999999,
    ])->assertRedirect($entry->getCpEditUrl());
});

it('returns 400 when no element is identified by the request', function () {
    postJson(action(EditElementController::class), [
        'elementType' => Entry::class,
        'siteId' => Sites::getPrimarySite()->id,
    ], [
        'X-Craft-Container-Id' => 'slideout',
    ])->assertBadRequest();
});

it('returns a json editor payload for the current element', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    getJson(action(EditElementController::class, [
        'elementType' => $entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ]), [
        'X-Craft-Container-Id' => 'slideout',
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('action', 'elements/save')
            ->where('notice', null)
            ->where('content', fn (string $content) => str_contains($content, 'craft-entry-field-layout-form')
                && str_contains($content, 'elements/save'))
            ->where('deltaNames', fn ($names) => collect($names)
                ->doesntContain(fn (string $name) => str_ends_with($name, '[title]')))
            ->where('bodyHtml', fn (string $html) => str_contains($html, sprintf('"elementId":%d', $entry->id))
                && str_contains($html, sprintf('"canonicalId":%d', $entry->id))
                && str_contains($html, '"isStatic":false')
                && str_contains($html, '"isProvisionalDraft":false')
                && str_contains($html, '"isUnpublishedDraft":false'))
            ->has('headHtml')
            ->has('bodyHtml')
            ->has('deltaNames')
            ->has('initialDeltaValues')
            ->etc()
        );
});

it('prevalidates enabled live elements and returns an error summary', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);

    postJson(action(EditElementController::class), [
        'elementType' => $entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'prevalidate' => 1,
        'title' => '',
    ], [
        'X-Craft-Container-Id' => 'slideout',
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('action', 'elements/save')
            ->where('errorSummary', fn (?string $summary) => is_string($summary)
                && str_contains($summary, 'The title field is required.')
                && str_contains($summary, 'field-error-key'))
            ->etc()
        );
});

it('renders draft editing controls for saved drafts', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
    /** @var Entry $draft */
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Working Draft');

    get(cp_url(sprintf(
        'entries/%s/%d-%s?draftId=%d',
        $entry->getSection()->handle,
        $entry->id,
        $entry->slug,
        $draft->draftId,
    )))
        ->assertOk()
        ->assertSeeText('Apply draft')
        ->assertSeeText(mb_ucfirst(t('Save {type}', ['type' => t('draft')])))
        ->assertSee('elements/save-draft', false);
});

it('renders revision notices and controls for revisions', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
    /** @var Entry $revision */
    $revision = Elements::getElementById(app(Revisions::class)->createRevision($entry, auth()->id(), 'Revision notes'));

    get(cp_url(sprintf(
        'entries/%s/%d-%s?revisionId=%d',
        $entry->getSection()->handle,
        $entry->id,
        $entry->slug,
        $revision->revisionId,
    )))
        ->assertOk()
        ->assertSeeText('viewing a revision')
        ->assertSeeText('Revert content from this revision')
        ->assertSee('elements/revert', false);
});

it('renders provisional draft notices when a provisional draft exists', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);

    get(cp_url(sprintf(
        'entries/%s/%d-%s',
        $entry->getSection()->handle,
        $entry->id,
        $entry->slug,
    )))
        ->assertOk()
        ->assertSeeText('Showing your unsaved changes.')
        ->assertSee('elements/apply-draft', false);
});

it('renders unpublished draft controls', function () {
    /** @var Entry $draft */
    $draft = app(Entry::class);
    $draft->siteId = Sites::getPrimarySite()->id;
    $draft->sectionId = $this->section->id;
    $draft->typeId = $this->entryType->id;
    $draft->title = 'Unpublished Draft';
    $draft->slug = Str::slug($draft->title);
    $draft->setAuthorIds([auth()->id()]);

    app(Drafts::class)->saveElementAsDraft($draft, auth()->id(), markAsSaved: false);

    get($draft->getCpEditUrl())
        ->assertOk()
        ->assertSeeText(mb_ucfirst(t('Create {type}', ['type' => Entry::lowerDisplayName()])))
        ->assertSee('elements/apply-draft', false);
});

it('merges canonical changes into outdated drafts before rendering', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
    /** @var Entry $draft */
    $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Working Draft');

    $entry->title = 'Updated Canonical Title';
    Elements::saveElement($entry);

    get(cp_url(sprintf(
        'entries/%s/%d-%s?draftId=%d',
        $entry->getSection()->handle,
        $entry->id,
        $entry->slug,
        $draft->draftId,
    )))
        ->assertOk()
        ->assertSeeText('Recent changes to the Current revision have been merged into this draft.');
});
