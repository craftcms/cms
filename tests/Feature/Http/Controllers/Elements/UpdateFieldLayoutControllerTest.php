<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Http\Controllers\Elements\UpdateFieldLayoutController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new EntryTitleField(['uid' => 'entry-title'])));
    $config = $layout->getConfig();
    $config['tabs'][0]['uid'] = 'entry-content';
    $layout = FieldLayoutModel::factory()->create(['type' => Entry::class, 'config' => $config]);
    $this->entryType = EntryType::factory()->create(['fieldLayoutId' => $layout->id]);
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'blog',
    ]);
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action(UpdateFieldLayoutController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('returns responses resolved by the element request', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    post(action(UpdateFieldLayoutController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'draftId' => 999999,
        'siteId' => $entry->siteId,
    ])->assertRedirect($entry->getCpEditUrl());
});

it('returns 400 when no element is identified by the request', function () {
    postJson(action(UpdateFieldLayoutController::class), [
        'elementType' => Entry::class,
        'elementId' => 999999,
        'siteId' => Sites::getPrimarySite()->id,
    ])->assertBadRequest();
});

it('returns 400 for revisions', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
    /** @var Entry $revision */
    $revision = Elements::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

    postJson(action(UpdateFieldLayoutController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'revisionId' => $revision->revisionId,
        'siteId' => $revision->siteId,
    ])->assertBadRequest();
});

it('returns updated field layout data for existing elements', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    postJson(action(UpdateFieldLayoutController::class), [
        'editor' => ['entry' => [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'title' => 'Updated Title',
            'slug' => 'updated-title',
        ]],
    ], [
        'X-Craft-Namespace' => 'editor[entry]',
    ])->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Field layout updated.')
            ->where('modelName', 'element')
            ->where('element.id', $entry->id)
            ->where('element.title', 'Updated Title')
            ->where('element.slug', 'updated-title')
            ->where('form.scope', ['editor', 'entry'])
            ->where('form.values.editor.entry.title', 'Updated Title')
            ->has('form.nodes')
            ->has('initialDeltaValues')
            ->has('headHtml')
            ->has('bodyHtml')
            ->etc()
        );
});

it('returns field layout data for new elements', function () {
    postJson(action(UpdateFieldLayoutController::class), [
        'elementType' => Entry::class,
        'siteId' => Sites::getPrimarySite()->id,
        'sectionId' => $this->section->id,
        'typeId' => $this->entryType->id,
        'title' => 'Unsaved Entry',
    ])->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Field layout updated.')
            ->where('modelName', 'element')
            ->where('element.title', 'Unsaved Entry')
            ->where('element.sectionId', $this->section->id)
            ->where('element.typeId', $this->entryType->id)
            ->where('element.slug', fn (string $slug) => $slug !== '')
            ->has('initialDeltaValues')
            ->has('headHtml')
            ->has('bodyHtml')
            ->etc()
        );
});
