<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\Users as UsersField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Import\Transformers\EntryTransformer;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;

beforeEach(function () {
    $this->import = app(Import::class);

    $allFields = [
        Field::factory()->create(['name' => 'My Entries', 'handle' => 'myEntries', 'type' => EntriesField::class]),
        Field::factory()->create(['name' => 'My Users', 'handle' => 'myUsers', 'type' => UsersField::class]),
        Field::factory()->create(['name' => 'My Assets', 'handle' => 'myAssets', 'type' => AssetsField::class]),
    ];

    Fields::refreshFields();

    $this->relatedUser = User::factory()->createElement();
    $this->relatedAsset = Asset::factory()->createElement();

    $layoutElements[] = new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]);
    foreach ($allFields as $field) {
        $layoutElements[] = CustomField::make($field->handle);
    }

    $fieldLayout = FieldLayout::factory()->withContentTab($layoutElements)->create();

    $entryType = EntryType::factory()
        ->withFieldLayout($fieldLayout)
        ->create(['name' => 'With Relation Fields', 'handle' => 'withRelationFields', 'hasTitleField' => true]);

    $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

    $seedResult = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withFieldLayout($fieldLayout)
        ->createElementWithFields(['title' => 'seed entry', 'slug' => 'seed-entry']);

    $this->section = $seedResult->element->getSection();
    $this->entryType = $seedResult->element->getType();

    $relatedResult = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withFieldLayout($fieldLayout)
        ->createElementWithFields(['title' => 'related entry', 'slug' => 'related-entry']);

    $this->relatedEntry = $relatedResult->element;

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->entryData = fn (array $fieldValues) => array_merge([
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
    ], $fieldValues);
});

it('imports an entries field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myEntries')->one()->id)->toBe($this->relatedEntry->id);
});

it('imports a users field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myUsers' => [$this->relatedUser->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myUsers')->one()->id)->toBe($this->relatedUser->id);
});

it('imports an assets field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myAssets' => [$this->relatedAsset->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myAssets')->one()->id)->toBe($this->relatedAsset->id);
});

it('imports an entries field value using a transformer with ID map', function () {
    $importer = (clone $this->importer)
        ->transformer(new class extends EntryTransformer
        {
            public function transform(mixed $item): array
            {
                $array = parent::transform($item);
                $array['myEntries'] = $item['myEntriesToBeMapped'];

                return $array;
            }
        });

    $this->import->importItem($importer, ($this->entryData)(['myEntriesToBeMapped' => [$this->relatedEntry->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myEntries')->one()->id)->toBe($this->relatedEntry->id);
});

it('imports an entries field value using a transformer with element lookup', function () {
    $importer = (clone $this->importer)
        ->transformer(new class extends EntryTransformer
        {
            public function transform(mixed $item): array
            {
                $array = parent::transform($item);
                $array['myEntries'] = EntryElement::find()->title($item['myEntriesToBeMapped'])->ids();

                return $array;
            }
        });

    $this->import->importItem($importer, ($this->entryData)(['myEntriesToBeMapped' => [$this->relatedEntry->title]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myEntries')->one()->id)->toBe($this->relatedEntry->id);
});
