<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\Users as UsersField;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Import\Transformers\EntryTransformer;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
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

    $layoutElements = array_map(fn ($field) => CustomField::make($field->handle), $allFields);

    $seed = ImportFixtures::seedEntry($layoutElements, ['name' => 'With Relation Fields', 'handle' => 'withRelationFields']);

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    $relatedResult = Entry::factory()
        ->forSection($seed->section)
        ->forEntryType($seed->entryType)
        ->withFieldLayout($seed->fieldLayout)
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
        'matchCriteria' => ['title' => 'title'],
    ], $fieldValues);
});

it('imports an entries field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    // assert the whole list, so an extra or reordered relation can't slip through
    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

it('imports a users field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myUsers' => [$this->relatedUser->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myUsers')->ids())->toBe([$this->relatedUser->id]);
});

it('imports an assets field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myAssets' => [$this->relatedAsset->id]]));
    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myAssets')->ids())->toBe([$this->relatedAsset->id]);
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
    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
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
    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

// the manual fixture lists ids in a deliberately non-ascending order ("image": [141, 140])
it('preserves the order of related element ids', function () {
    $second = Entry::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElementWithFields(['title' => 'second related entry', 'slug' => 'second-related-entry'])
        ->element;

    $this->import->importItem($this->importer, ($this->entryData)([
        'myEntries' => [$second->id, $this->relatedEntry->id],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([$second->id, $this->relatedEntry->id]);
});

it('leaves existing relations alone when the field is absent from a later Import', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $this->import->importItem($importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $this->import->importItem($importer, ($this->entryData)([]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

// an empty value only clears a field that's marked clearable - the same rule ImportClearableItemsTest
// covers for plain text, here for relations
it('leaves existing relations alone when an empty list is provided and the field is not clearable', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $this->import->importItem($importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $this->import->importItem($importer, ($this->entryData)(['myEntries' => []]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([$this->relatedEntry->id]);
});

// applyClearableItems() turns the empty list into null before the importer sees it (the same path
// that clears a plain text field in ImportClearableItemsTest), but the relation field keeps its
// existing targets.
it('clears existing relations when an empty list is provided for a clearable field', function () {
    $importer = (clone $this->importer)
        ->matchCriteria(['title' => 'title'])
        ->clearableItems(['myEntries']);

    $this->import->importItem($importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));
    $this->import->importItem($importer, ($this->entryData)(['myEntries' => []]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myEntries')->ids())->toBe([]);
})->skip('an empty value does not clear a relation field even when it is marked clearable, unlike a plain text field');
