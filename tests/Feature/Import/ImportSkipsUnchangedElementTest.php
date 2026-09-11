<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->import = app(Import::class);

    $textField = Field::factory()->create(['name' => 'My Plain Text', 'handle' => 'myPlainText', 'type' => PlainText::class]);
    $relationField = Field::factory()->create(['name' => 'My Entries', 'handle' => 'myEntries', 'type' => EntriesField::class]);
    $blockTextField = ImportFixtures::plainTextField('blockText', 'Block Text');
    $blockEntryType = ImportFixtures::blockEntryType('blockEt', [$blockTextField], 'Block ET');
    $matrixField = ImportFixtures::matrixField('myMatrix', [$blockEntryType], 'My Matrix');

    Fields::refreshFields();

    $seed = ImportFixtures::seedEntry(
        [
            CustomField::make($textField->handle),
            CustomField::make($relationField->handle),
            CustomField::make($matrixField->handle),
        ],
        ['name' => 'With Fields', 'handle' => 'withFields'],
    );

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    // the seeded entry is both the element the imports below match against, and the element the
    // relation field points at
    $this->relatedEntry = $seed->entry;

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null)
        ->matchCriteria(['title' => 'title']);

    $this->entryData = fn (array $fieldValues = []) => array_merge([
        'title' => 'seed entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myPlainText' => 'original text',
        'matchCriteria' => ['title' => 'title'],
    ], $fieldValues);

    // establish the initial field value through the importer itself, so re-imports below can compare against it
    $this->import->importItem($this->importer, ($this->entryData)());

    // count ElementSaving occurrences from here on, so the seeding save above isn't counted
    $this->saveCount = 0;
    Event::listen(ElementSaving::class, function () {
        $this->saveCount++;
    });
});

it('does not save when re-importing identical attribute and field values', function () {
    $this->import->importItem($this->importer, ($this->entryData)());

    expect($this->saveCount)->toBe(0);
});

it('saves when re-importing with a changed attribute', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['postDate' => '2020-06-15 12:00:00']));

    expect($this->saveCount)->toBeGreaterThan(0);

    $entry = EntryElement::find()->title('seed entry')->status(null)->one();
    expect($entry->postDate->year)->toBe(2020);
});

it('saves when re-importing with a changed field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myPlainText' => 'updated text']));

    expect($this->saveCount)->toBe(1);

    $entry = EntryElement::find()->title('seed entry')->status(null)->one();
    expect($entry->getFieldValue('myPlainText'))->toBe('updated text');
});

it('saves when re-importing with a changed relation field value', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myEntries' => [$this->relatedEntry->id]]));

    expect($this->saveCount)->toBeGreaterThan(0);

    $entry = EntryElement::find()->title('seed entry')->status(null)->one();
    expect($entry->getFieldValue('myEntries')->one()->id)->toBe($this->relatedEntry->id);
});

it('always saves a brand-new element even when mapped values match field defaults', function () {
    $this->import->importItem($this->importer, [
        'title' => 'brand new entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
    ]);

    expect($this->saveCount)->toBeGreaterThan(0);

    $entry = EntryElement::find()->title('brand new entry')->status(null)->one();
    expect($entry)->not->toBeNull();
});

// The fix for "a container field the element has nothing for yet" must not turn into "always save
// when container data is present" - a matrix whose blocks are unchanged still diffs correctly.
it('does not save when re-importing a row whose matrix blocks are unchanged', function () {
    $blocks = [
        [
            'type' => 'blockEt',
            'title' => 'block 1',
            'matchCriteria' => ['title' => 'title'],
            'fields' => ['blockText' => 'one'],
        ],
    ];

    // the seed import in beforeEach had no matrix data, so this one creates the blocks
    $this->import->importItem($this->importer, ($this->entryData)(['myMatrix' => $blocks]));
    expect($this->saveCount)->toBeGreaterThan(0);

    $this->saveCount = 0;

    $this->import->importItem($this->importer, ($this->entryData)(['myMatrix' => $blocks]));

    expect($this->saveCount)->toBe(0)
        ->and(EntryElement::find()->title('seed entry')->status(null)->one()->getFieldValue('myMatrix')->count())->toBe(1);
});
