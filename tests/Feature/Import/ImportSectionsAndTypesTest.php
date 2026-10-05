<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
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

    $this->importer = EntryImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null)
        ->matchCriteria(['title' => 'title']);

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

it('uses each row’s own entry type when the importer has no field layout provider', function () {
    ($this->importRows)([$this->rows[2]]);

    expect(EntryElement::find()->title('row with the second section’s own type')->status(null)->one()->getType()->handle)
        ->toBe('secondSectionOnlyType');
});

it('skips a row whose entry type is not allowed in its section', function () {
    expect(fn () => ($this->importRows)([[
        'title' => 'row with a disallowed type',
        'sectionId' => 'firstImportSection',
        'typeId' => 'secondSectionOnlyType',
        'plainText' => 'mismatched',
        'matchCriteria' => ['title' => 'title'],
    ]]))->toThrow(InvalidElementException::class);

    expect(EntryElement::find()->title('row with a disallowed type')->status(null)->one())->toBeNull();
});
