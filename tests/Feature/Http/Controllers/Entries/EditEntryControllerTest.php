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
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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
    ]);
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

it('includes the meta fields and metadata as server-rendered islands', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sidebarHtml', fn (?string $html) => is_string($html) && str_contains($html, 'name="slug"'))
            ->where('metadataHtml', fn (?string $html) => is_string($html) && $html !== '')
            ->etc()
        );
});

it('falls back to the legacy editor for drafts', function () {
    $draft = app(Drafts::class)->createDraft($this->entry, auth()->id(), name: 'Working Draft');

    get(cp_url(sprintf(
        'entries/news/%d-%s?draftId=%d',
        $this->entry->id,
        $this->entry->slug,
        $draft->draftId,
    )))
        ->assertOk()
        ->assertSee('elements/save-draft', false);
});

it('falls back to the legacy editor for revisions', function () {
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
        ->assertSee('elements/revert', false);
});

it('falls back to the legacy editor when a provisional draft exists', function () {
    app(Drafts::class)->createDraft($this->entry, auth()->id(), provisional: true);

    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertSee('elements/apply-draft', false);
});
