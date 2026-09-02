<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;

beforeEach(function () {
    $this->import = app(Import::class);

    $plainTextField = Field::factory()->create([
        'name' => 'My Plain Text',
        'handle' => 'myPlainText',
        'type' => PlainText::class,
    ]);

    Fields::refreshFields();

    $fieldLayout = FieldLayout::factory()
        ->withContentTab([
            new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $entryType = EntryType::factory()
        ->withFieldLayout($fieldLayout)
        ->create(['name' => 'With Plain Text', 'handle' => 'withPlainText', 'hasTitleField' => true]);

    $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

    $seedResult = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withFieldLayout($fieldLayout)
        ->createElementWithFields(['title' => 'imported entry', 'slug' => 'imported-entry']);

    $this->section = $seedResult->element->getSection();
    $this->entryType = $seedResult->element->getType();

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->matchCriteria(['title' => 'title'])
        ->transformer(null);

    $this->entryData = fn (array $fieldValues = []) => array_merge([
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
    ], $fieldValues);

    // seed the field value through the same import pipeline, since the entry factory can't
    // write directly to a custom field's content column
    $this->import->importItem($this->importer, ($this->entryData)(['myPlainText' => 'original value']));
});

it('clears an existing field value when the field is not mapped/provided at all and is marked clearable', function () {
    $importer = (clone $this->importer)->clearableItems(['myPlainText']);

    $this->import->importItem($importer, ($this->entryData)());

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myPlainText'))->toBeNull();
});

it('clears an existing field value when the provided value is an empty string and the field is marked clearable', function () {
    $importer = (clone $this->importer)->clearableItems(['myPlainText' => true]);

    $this->import->importItem($importer, ($this->entryData)(['myPlainText' => '']));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myPlainText'))->toBeNull();
});

it('clears an existing field value when the provided value is whitespace only and the field is marked clearable', function () {
    $importer = (clone $this->importer)->clearableItems(['myPlainText' => true]);

    $this->import->importItem($importer, ($this->entryData)(['myPlainText' => '   ']));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myPlainText'))->toBeNull();
});

// KNOWN FAILURE: unrelated to clearableItems. ElementImporter::getRootElement() builds its lookup
// query with ->drafts()->status(null) — ->drafts() defaults to $value=true, which inner-joins the
// drafts table and therefore matches ONLY drafts, excluding canonical/live elements entirely (see
// QueriesDraftsAndRevisions::applyDraftParams()). So matchCriteria never finds the canonical entry
// seeded in beforeEach(), a brand new entry gets created on every import instead, and the "found"
// entry below is actually a different, unrelated row with no field value set. Left red intentionally
// until ->drafts() is fixed to ->drafts(null) (or similar) separately.
it('leaves an existing field value untouched when the field is not marked clearable, even if the value is missing/empty', function () {
    $this->import->importItem($this->importer, ($this->entryData)());

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myPlainText'))->toBe('original value');
});

it('leaves an existing field value untouched when the field is not marked clearable and an empty value is explicitly provided', function () {
    $this->import->importItem($this->importer, ($this->entryData)(['myPlainText' => '']));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myPlainText'))->toBe('original value');
});

it('leaves a provided non-empty value alone even when the field is marked clearable', function () {
    $importer = (clone $this->importer)->clearableItems(['myPlainText']);

    $this->import->importItem($importer, ($this->entryData)(['myPlainText' => 'a new value']));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myPlainText'))->toBe('a new value');
});

it('normalizes a flat list of dot-notation handles into the nested truthy-leaf shape', function () {
    $importer = ElementImporter::create()->clearableItems(['myPlainText', 'some.nested.handle']);

    expect($importer->clearableItems)->toBe([
        'myPlainText' => true,
        'some' => ['nested' => ['handle' => true]],
    ]);
});

it('accepts an already-nested truthy-leaf shape as-is', function () {
    $importer = ElementImporter::create()->clearableItems(['myPlainText' => 1]);

    expect($importer->clearableItems)->toBe(['myPlainText' => 1]);
});

// The following two tests verify the raw $data mutation in isolation, via a no-op importer that just
// captures what it's handed — decoupled from Entry/Element persistence, since the "leaves untouched"
// tests above show persistence has its own unrelated, pre-existing bug for matched-element updates.
it('strips a non-clearable, empty top-level key from the data entirely, before the importer ever sees it', function () {
    $importer = new class extends BaseImporter
    {
        public array $capturedData = [];

        #[Override]
        public function importItem(array $data): void
        {
            $this->capturedData = $data;
        }
    };
    $importer->matchCriteria([]);
    $importer->clearableItems(['heading' => true]);

    $this->import->importItem($importer, ['title' => 'foo', 'body' => '', 'heading' => '']);

    expect($importer->capturedData)->toBe(['title' => 'foo', 'heading' => null]);
});

it('strips a non-clearable, empty nested key inside a declared container, while keeping the clearable one', function () {
    $importer = new class extends BaseImporter
    {
        public array $capturedData = [];

        #[Override]
        public function importItem(array $data): void
        {
            $this->capturedData = $data;
        }
    };
    $importer->matchCriteria([]);
    $importer->clearableItems(['myMatrix' => ['blockEt' => ['fields' => ['plainText' => true]]]]);

    $this->import->importItem($importer, [
        'title' => 'foo',
        'myMatrix' => [
            ['type' => 'blockEt', 'title' => 'block 1', 'fields' => ['plainText' => '', 'other' => '']],
        ],
    ]);

    expect($importer->capturedData['myMatrix'][0]['fields'])->toBe(['plainText' => null]);
});

describe('nested matrix clearing', function () {
    beforeEach(function () {
        $plainTextField = Field::factory()->create([
            'name' => 'Plain Text',
            'handle' => 'plainText',
            'type' => PlainText::class,
        ]);

        $blockEntryType = EntryType::factory()
            ->withField($plainTextField)
            ->create(['name' => 'Block ET', 'handle' => 'blockEt', 'hasTitleField' => true]);

        $matrixField = Field::factory()->create([
            'name' => 'My Matrix',
            'handle' => 'myMatrix',
            'type' => Matrix::class,
            'settings' => ['entryTypes' => [$blockEntryType->id]],
        ]);

        Fields::refreshFields();

        $fieldLayout = FieldLayout::factory()
            ->withContentTab([
                new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                CustomField::make($matrixField->handle),
            ])
            ->create();

        $entryType = EntryType::factory()
            ->withFieldLayout($fieldLayout)
            ->create(['name' => 'With Matrix', 'handle' => 'withMatrix', 'hasTitleField' => true]);

        $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

        $result = Entry::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->withFieldLayout($fieldLayout)
            ->createElementWithFields(['title' => 'matrix entry', 'slug' => 'matrix-entry']);

        $this->matrixSection = $result->element->getSection();
        $this->matrixEntryType = $result->element->getType();

        $this->matrixImporter = ElementImporter::create()
            ->className(EntryElement::class)
            ->site(Sites::getPrimarySite()->handle)
            ->matchCriteria(['title' => 'title'])
            ->clearableItems(['myMatrix' => ['blockEt' => ['fields' => ['plainText' => true]]]])
            ->transformer(null);

        $this->matrixEntryData = fn (array $blocks) => [
            'title' => 'matrix entry',
            'sectionId' => $this->matrixSection->handle,
            'typeId' => $this->matrixEntryType->handle,
            'myMatrix' => $blocks,
        ];
    });

    it('clears an existing block field value when it is missing on a later import', function () {
        $this->import->importItem($this->matrixImporter, ($this->matrixEntryData)([
            [
                'type' => 'blockEt',
                'title' => 'block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => ['plainText' => 'foo'],
            ],
        ]));

        $entry = EntryElement::find()->title('matrix entry')->one();
        $block = $entry->getFieldValue('myMatrix')->one();
        expect($block->getFieldValue('plainText'))->toBe('foo');

        $this->import->importItem($this->matrixImporter, ($this->matrixEntryData)([
            [
                'type' => 'blockEt',
                'title' => 'block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => [],
            ],
        ]));

        $entry = EntryElement::find()->title('matrix entry')->one();
        $block = $entry->getFieldValue('myMatrix')->one();
        expect($block->getFieldValue('plainText'))->toBeNull();
    });
});
