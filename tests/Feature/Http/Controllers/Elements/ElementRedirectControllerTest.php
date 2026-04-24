<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes as EntryTypesFacade;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\User\Elements\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());

    $this->edition = Edition::get();
    $this->tempAssetUploadFs = Cms::config()->tempAssetUploadFs;
    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'news',
    ]);
});

afterEach(function () {
    Edition::set($this->edition);
    Cms::config()->tempAssetUploadFs = $this->tempAssetUploadFs;
});

it('returns redirect responses returned by the element request for id routes', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    get(cp_url("edit/$entry->id-$entry->slug")."?draftId=999999&siteId=$entry->siteId")
        ->assertRedirect($entry->getCpEditUrl());
});

it('returns redirect responses returned by the element request for uuid routes', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    get(cp_url("edit/$entry->uid")."?draftId=999999&siteId=$entry->siteId")
        ->assertRedirect($entry->getCpEditUrl());
});

it('redirects to non-standard control panel edit urls', function () {
    $entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    get(cp_url("edit/$entry->id-$entry->slug"))
        ->assertRedirect($entry->getCpEditUrl());
});

it('aborts when the element has no control panel edit url', function () {
    config()->set('filesystems.disks.element-redirect-temp-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/element-redirect-controller/temp-disk'),
    ]);
    Cms::config()->tempAssetUploadFs = 'disk:element-redirect-temp-disk';

    $volume = Volume::factory()->create(['fs' => 'disk:element-redirect-temp-disk']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'temp-file.jpg',
        'uploaderId' => auth()->id(),
    ]);

    $this->withoutExceptionHandling();

    expect(fn () => get(cp_url("edit/$asset->id-test")))
        ->toThrow(HttpException::class, 'The element doesn’t have an edit page.');
});

it('returns inline edit responses for standard control panel edit urls', function () {
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $matrixEntryType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Matrix Block',
            'handle' => 'matrixBlock',
            'hasTitleField' => true,
        ]);

    $matrixField = Field::factory()->create([
        'name' => 'Matrix Field',
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);

    $ownerType = EntryType::factory()
        ->withField($matrixField)
        ->create([
            'name' => 'Owner',
            'handle' => 'owner',
            'hasTitleField' => true,
        ]);

    $section = Section::factory()
        ->withEntryTypes($ownerType)
        ->create([
            'handle' => 'owners',
        ]);

    $owner = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement([
            'title' => 'Owner Entry',
            'slug' => 'owner-entry',
        ]);

    EntryTypesFacade::refreshEntryTypes();
    FieldsFacade::invalidateCaches();
    FieldsFacade::refreshFields();

    $matrixField = FieldsFacade::getFieldById($matrixField->id);
    /** @var Entry $owner */
    $owner = Entry::find()->id($owner->id)->status(null)->one();

    $blockUid = fake()->uuid();
    $owner->setFieldValueFromRequest('matrixField', [
        'entries' => [
            "uid:$blockUid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Inline Block',
                'enabled' => true,
                'fields' => [
                    'innerText' => 'Inline block content',
                ],
            ],
        ],
        'sortOrder' => [$blockUid],
    ]);

    expect(Elements::saveElement($owner))->toBeTrue();

    /** @var Entry $entry */
    $entry = Entry::find()
        ->fieldId($matrixField->id)
        ->ownerId($owner->id)
        ->siteId($owner->siteId)
        ->status(null)
        ->one();

    expect($entry->getCpEditUrl())->toStartWith(cp_url('edit'));

    get(cp_url("edit/$entry->id-$entry->slug"))
        ->assertOk()
        ->assertSeeText('Inline Block')
        ->assertSee('elements/save', false);
});
