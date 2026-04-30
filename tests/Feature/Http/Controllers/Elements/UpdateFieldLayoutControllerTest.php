<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
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

    $this->entryType = EntryType::factory()->create();
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
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'title' => 'Updated Title',
        'slug' => 'updated-title',
    ])->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Field layout updated.')
            ->where('modelName', 'element')
            ->where('element.id', $entry->id)
            ->where('element.title', 'Updated Title')
            ->where('element.slug', 'updated-title')
            ->has('missingElements')
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
            ->has('missingElements')
            ->has('initialDeltaValues')
            ->has('headHtml')
            ->has('bodyHtml')
            ->etc()
        );
});

it('normalizes user photoId arrays from element select inputs', function () {
    config()->set('filesystems.disks.update-field-layout-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/update-field-layout-test'),
    ]);

    $user = User::findOne();
    $volume = Volume::factory()->create(['fs' => 'disk:update-field-layout-test']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'kind' => 'image',
        'filename' => 'avatar.jpg',
    ]);

    postJson(action(UpdateFieldLayoutController::class), [
        'elementType' => User::class,
        'elementId' => $user->id,
        'siteId' => $user->siteId,
        'photoId' => [$asset->id],
    ])
        ->assertOk()
        ->assertJsonPath('element.photoId', $asset->id);
});
