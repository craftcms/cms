<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $this->import = app(Import::class);

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
    Fields::refreshFields();

    $this->sharedType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($plainTextField->handle)],
        ['name' => 'Shared Type', 'handle' => 'sharedType'],
    );

    // only allowed in the second section, like withPlainText2 is only in one of the manual sections
    $this->secondSectionOnlyType = ImportFixtures::entryTypeWithTitle(
        [CustomField::make($plainTextField->handle)],
        ['name' => 'Second Section Only', 'handle' => 'secondSectionOnlyType'],
    );

    $this->firstSection = Section::factory()
        ->withEntryTypes($this->sharedType)
        ->create(['handle' => 'firstImportSection', 'minAuthors' => 0]);

    $this->secondSection = Section::factory()
        ->withEntryTypes($this->sharedType, $this->secondSectionOnlyType)
        ->create(['handle' => 'secondImportSection', 'minAuthors' => 0]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null)
        ->matchCriteria(['title' => 'title']);

    // a single payload spanning both sections and both entry types, as the manual
    // "different sections and ets" fixture does
    $this->rows = [
        [
            'title' => 'row in the first section',
            'sectionId' => 'firstImportSection',
            'typeId' => 'sharedType',
            'plainText' => 'one',
            'matchCriteria' => ['title' => 'title'],
        ],
        [
            'title' => 'row in the second section',
            'sectionId' => 'secondImportSection',
            'typeId' => 'sharedType',
            'plainText' => 'two',
            'matchCriteria' => ['title' => 'title'],
        ],
        [
            'title' => 'row with the second section’s own type',
            'sectionId' => 'secondImportSection',
            'typeId' => 'secondSectionOnlyType',
            'plainText' => 'three',
            'matchCriteria' => ['title' => 'title'],
        ],
    ];

    $this->importRows = function (array $rows) {
        foreach ($rows as $row) {
            $this->import->importItem($this->importer, $row);
        }
    };
});

it('imports rows into different sections and entry types from one payload', function () {
    ($this->importRows)($this->rows);

    $first = EntryElement::find()->title('row in the first section')->status(null)->one();
    $second = EntryElement::find()->title('row in the second section')->status(null)->one();
    $third = EntryElement::find()->title('row with the second section’s own type')->status(null)->one();

    expect($first->sectionId)->toBe($this->firstSection->id)
        ->and($first->getType()->handle)->toBe('sharedType')
        ->and($second->sectionId)->toBe($this->secondSection->id)
        ->and($second->getType()->handle)->toBe('sharedType')
        ->and($third->sectionId)->toBe($this->secondSection->id)
        ->and($third->getType()->handle)->toBe('secondSectionOnlyType');
});

it('re-imports the same payload without duplicating any row', function () {
    ($this->importRows)($this->rows);
    $ids = EntryElement::find()->status(null)->ids();

    ($this->importRows)($this->rows);

    expect(EntryElement::find()->status(null)->ids())->toBe($ids);
});

// Each row carries its own typeId and the importer has no field layout provider, so the row wins -
// the opposite of ImportEntryTest's "uses the entry type selected via the field layout provider",
// where the importer is editable and its provider takes over.
it('uses each row’s own entry type when the importer has no field layout provider', function () {
    ($this->importRows)([$this->rows[2]]);

    expect(EntryElement::find()->title('row with the second section’s own type')->status(null)->one()->getType()->handle)
        ->toBe('secondSectionOnlyType');
});

// Validation rejects the entry, so the row is skipped (logged as a warning) rather than imported
// under a type its section doesn't allow.
it('skips a row whose entry type is not allowed in its section', function () {
    ($this->importRows)([[
        'title' => 'row with a disallowed type',
        'sectionId' => 'firstImportSection',
        'typeId' => 'secondSectionOnlyType',
        'plainText' => 'mismatched',
        'matchCriteria' => ['title' => 'title'],
    ]]);

    expect(EntryElement::find()->title('row with a disallowed type')->status(null)->one())->toBeNull();
});
