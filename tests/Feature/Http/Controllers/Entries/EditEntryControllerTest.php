<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::findOne());

    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new EntryTitleField(['uid' => 'entry-title'])));
    $config = $layout->getConfig();
    $config['tabs'][0]['uid'] = 'entry-content';
    $layout = FieldLayoutModel::factory()->create(['type' => Entry::class, 'config' => $config]);
    $this->entryType = EntryType::factory()->create(['fieldLayoutId' => $layout->id]);
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'news',
        'enableVersioning' => true,
        'previewTargets' => [
            ['label' => 'Primary entry page', 'urlFormat' => '{url}', 'refresh' => '1'],
        ],
    ]);
    // The site settings factory randomizes `hasUrls`, which would make preview
    // targets come and go between runs.
    SectionSiteSettings::query()
        ->where('sectionId', $this->section->id)
        ->update([
            'hasUrls' => true,
            'uriFormat' => 'news/{slug}',
            'template' => 'news/_entry',
        ]);
    Sections::refreshSections();

    $this->entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);
});

it('renders the entry edit screen as an Inertia page', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Edit')
            ->where('elementId', $this->entry->id)
            ->where('canonicalId', $this->entry->id)
            ->where('elementType', Entry::class)
            ->where('siteId', $this->entry->siteId)
            ->where('title', 'Current Title')
            ->where('sectionHandle', 'news')
            ->where('saveId', $this->entry->id)
            ->where('readOnly', false)
        );
});

it('compiles the field layout into a form payload', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('form.nodes')
            ->where('form.nodes', fn (Collection $nodes) => $nodes
                ->contains(fn (array $node) => ($node['uid'] ?? null) === 'entry-content'))
            ->etc()
        );
});

it('points the form at the entry save action', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('saveUrl', fn (string $url) => str_contains($url, 'entries/save-entry'))
            ->etc()
        );
});

it('compiles the meta fields into a sidebar form', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sidebarForm.nodes', function (Collection $nodes) {
                $paths = $nodes
                    ->map(fn (array $node) => implode('.', $node['control']['path'] ?? []))
                    ->all();

                return in_array('slug', $paths, true)
                    && in_array('postDate', $paths, true)
                    && in_array('expiryDate', $paths, true)
                    && in_array('enabled', $paths, true)
                    && in_array('notes', $paths, true);
            })
            ->where('metadataHtml', fn (?string $html) => is_string($html) && $html !== '')
            ->etc()
        );
});

it('saves the meta fields the sidebar form submits', function () {
    post(action(StoreEntryController::class), [
        'entryId' => $this->entry->id,
        'siteId' => $this->entry->siteId,
        'typeId' => $this->entry->typeId,
        'title' => 'Retitled',
        'slug' => 'retitled-slug',
        'enabled' => '1',
        'postDate' => ['date' => '2027-03-04', 'time' => '09:30'],
    ])->assertRedirect();

    $entry = Entry::find()->id($this->entry->id)->status(null)->one();

    expect($entry->title)->toBe('Retitled')
        ->and($entry->slug)->toBe('retitled-slug')
        ->and($entry->postDate->format('Y-m-d'))->toBe('2027-03-04');
});

it('offers a Create a draft button on a canonical entry', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('headerActions', function (Collection $actions) {
                $create = $actions->firstWhere('label', 'Create a draft');

                return $create !== null
                    && str_contains((string) $create['actionUrl'], 'elements/save-draft')
                    && ($create['params']['dropProvisional'] ?? null) === 1
                    && is_string($create['redirect']);
            })
            ->etc()
        );
});

it('offers the alternate save actions beside Save', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('formActions', function (Collection $actions) {
                $labels = $actions->pluck('label');

                return $labels->contains('Save and continue editing')
                    && $labels->contains('Save and add another')
                    && $actions->contains(fn (array $action) => str_contains(
                        (string) ($action['actionUrl'] ?? ''),
                        'elements/duplicate',
                    ));
            })
            ->etc()
        );
});

it('renders a named draft in the Inertia editor', function () {
    $draft = app(Drafts::class)->createDraft($this->entry, auth()->id(), name: 'Working Draft');

    get(cp_url(sprintf(
        'entries/news/%d-%s?draftId=%d',
        $this->entry->id,
        $this->entry->slug,
        $draft->draftId,
    )))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Edit')
            ->where('draftId', $draft->draftId)
            ->where('isProvisionalDraft', false)
            ->where('readOnly', false)
            ->where('submitButtonLabel', 'Save draft')
            ->where('headerActions', fn (Collection $actions) => $actions
                ->pluck('label')->contains('Apply draft'))
            ->etc()
        );
});

it('renders a revision read-only in the Inertia editor', function () {
    $revision = Elements::getElementById(
        app(Revisions::class)->createRevision($this->entry, auth()->id(), 'Revision notes'),
    );

    get(cp_url(sprintf(
        'entries/news/%d-%s?revisionId=%d',
        $this->entry->id,
        $this->entry->slug,
        $revision->revisionId,
    )))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Edit')
            ->where('readOnly', true)
            ->where('canAutosave', false)
            ->where('notice', fn (?string $notice) => is_string($notice)
                && str_contains($notice, 'viewing a revision'))
            ->where('headerActions', fn (Collection $actions) => $actions
                ->pluck('label')->contains('Revert content from this revision'))
            ->etc()
        );
});

it('lists drafts and revisions in the context menu', function () {
    app(Drafts::class)->createDraft($this->entry, auth()->id(), name: 'Working Draft');
    app(Revisions::class)->createRevision($this->entry, auth()->id(), 'Revision notes');

    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('contextMenu.items', function (Collection $items) {
                $labels = $items->pluck('label');

                return $labels->contains('Current')
                    && $labels->contains('Drafts')
                    && $labels->contains('Working Draft')
                    && $items->contains(fn (array $item) => ($item['selected'] ?? false) === true);
            })
            ->etc()
        );
});

it('caps the context menu at five revisions, listing every draft', function () {
    foreach (range(1, 7) as $i) {
        app(Drafts::class)->createDraft($this->entry, auth()->id(), name: "Draft $i");
        app(Revisions::class)->createRevision($this->entry, auth()->id(), "Revision $i");
    }

    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('contextMenu.items', function (Collection $items) {
                // The flat list is grouped by headings, so count the links
                // between each heading and whatever follows it.
                $group = function (string $heading) use ($items): Collection {
                    $start = $items->search(
                        fn (array $item): bool => ($item['label'] ?? null) === $heading,
                    );

                    return $items
                        ->slice($start + 1)
                        ->takeWhile(fn (array $item): bool => ($item['type'] ?? null) === 'link');
                };

                return $group('Drafts')->count() === 7
                    && $group('Recent Revisions')->count() === 5;
            })
            ->etc()
        );
});

it('renders a provisional draft in the Inertia editor', function () {
    $draft = app(Drafts::class)->createDraft($this->entry, auth()->id(), provisional: true);

    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Edit')
            ->where('isProvisionalDraft', true)
            ->where('draftId', $draft->draftId)
            ->where('canonicalId', $this->entry->id)
            ->where('notice', 'Showing your unsaved changes.')
            ->where('applyDraftUrl', fn (string $url) => str_contains($url, 'elements/apply-draft'))
            ->etc()
        );
});

it('autosaves against the shared draft action', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('autosaveUrl', fn (string $url) => str_contains($url, 'elements/save-draft'))
            ->where('discardDraftUrl', fn (string $url) => str_contains($url, 'elements/delete-draft'))
            ->where('canAutosave', true)
            ->where('isProvisionalDraft', false)
            ->where('draftId', null)
            ->etc()
        );
});

it('renders an unpublished draft as a create screen', function () {
    $draft = app(Entry::class);
    $draft->siteId = $this->entry->siteId;
    $draft->sectionId = $this->section->id;
    $draft->typeId = $this->entryType->id;
    $draft->title = 'Unpublished Draft';
    $draft->slug = 'unpublished-draft';
    $draft->setAuthorIds([auth()->id()]);

    app(Drafts::class)->saveElementAsDraft($draft, auth()->id(), markAsSaved: false);

    get($draft->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('content/Edit')
            ->where('submitButtonLabel', 'Create entry')
            ->where('contextMenu', null)
            ->etc()
        );
});

it('exposes the action menu as behavior descriptors', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('actionMenu', function (Collection $items) {
                $labels = $items->pluck('label');
                $behaviors = $items->pluck('behavior.type');

                return $labels->contains('Validate entry')
                    && $labels->contains('Copy entry')
                    && $labels->contains('Entry type settings')
                    && $labels->contains('Section settings')
                    && $behaviors->contains('submit')
                    && $behaviors->contains('copy')
                    && $behaviors->contains('slideout');
            })
            ->etc()
        );
});

it('routes deletion through the deletion-blockers flow', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('actionMenu', function (Collection $items) {
                $delete = $items->first(fn (array $item) => ($item['behavior']['type'] ?? null) === 'delete');

                return $delete !== null
                    && ($delete['destructive'] ?? false) === true
                    && $delete['behavior']['elementId'] === $this->entry->id
                    && is_string($delete['behavior']['confirm'])
                    && is_string($delete['behavior']['redirect']);
            })
            ->etc()
        );
});

it('omits the Edit action and never offers it on the edit screen', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('actionMenu', fn (Collection $items) => $items
                ->pluck('label')
                ->doesntContain(fn (string $label) => str_starts_with($label, 'Edit ')))
            ->etc()
        );
});

it('drops the View action for revisions, which have no editable URL context', function () {
    $revision = Elements::getElementById(
        app(Revisions::class)->createRevision($this->entry, auth()->id(), 'Revision notes'),
    );

    get(cp_url(sprintf(
        'entries/news/%d-%s?revisionId=%d',
        $this->entry->id,
        $this->entry->slug,
        $revision->revisionId,
    )))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('actionMenu', fn (Collection $items) => $items
                ->pluck('label')->doesntContain('Validate entry'))
            ->etc()
        );
});

it('links preview targets straight at the element when it is live', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previewTargets', fn (Collection $targets) => $targets->isNotEmpty()
                && $targets->every(fn (array $target) => ! str_contains((string) $target['url'], 'preview/create-token')))
            ->etc()
        );
});

it('links preview targets through a token when the element is not public', function () {
    $draft = app(Drafts::class)->createDraft($this->entry, auth()->id(), name: 'Working Draft');

    get(cp_url(sprintf(
        'entries/news/%d-%s?draftId=%d',
        $this->entry->id,
        $this->entry->slug,
        $draft->draftId,
    )))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('previewTargets', fn (Collection $targets) => $targets->isNotEmpty()
                && $targets->every(fn (array $target) => str_contains((string) $target['url'], 'preview/create-token')
                    && str_contains((string) $target['url'], 'redirect=')))
            ->etc()
        );
});

it('polls for activity against the canonical element', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('activityUrl', fn (?string $url) => is_string($url)
                && str_contains($url, 'elements/recent-activity')
                && str_contains($url, 'dontExtendSession'))
            ->where('updatedTimestamps', fn (Collection $stamps) => $stamps->has('element')
                && $stamps->has('canonical'))
            ->where('elementDisplayName', 'entry')
            ->etc()
        );
});

it('does not poll for activity on a revision', function () {
    $revision = Elements::getElementById(
        app(Revisions::class)->createRevision($this->entry, auth()->id(), 'Revision notes'),
    );

    get(cp_url(sprintf(
        'entries/news/%d-%s?revisionId=%d',
        $this->entry->id,
        $this->entry->slug,
        $revision->revisionId,
    )))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('activityUrl', null)
            ->etc()
        );
});
