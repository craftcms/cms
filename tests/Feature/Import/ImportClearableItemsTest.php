<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $this->import = app(Import::class);

    $plainTextField = Field::factory()->create([
        'name' => 'My Plain Text',
        'handle' => 'myPlainText',
        'type' => PlainText::class,
    ]);

    Fields::refreshFields();

    $seed = ImportFixtures::seedEntry(
        [CustomField::make($plainTextField->handle)],
        ['name' => 'With Plain Text', 'handle' => 'withPlainText'],
        entryAttrs: ['title' => 'imported entry', 'slug' => 'imported-entry'],
    );

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    $this->importer = EntryImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->matchCriteria(['title' => 'title'])
        ->transformer(null);

    $this->entryData = fn (array $fieldValues = []) => array_merge([
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'matchCriteria' => ['title' => 'title'],
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
    $importer = EntryImporter::create()->clearableItems(['myPlainText', 'some.nested.handle']);

    expect($importer->clearableItems)->toBe([
        'myPlainText' => true,
        'some' => ['nested' => ['handle' => true]],
    ]);
});

it('accepts an already-nested truthy-leaf shape as-is', function () {
    $importer = EntryImporter::create()->clearableItems(['myPlainText' => 1]);

    expect($importer->clearableItems)->toBe(['myPlainText' => 1]);
});

describe('nested matrix clearing', function () {
    beforeEach(function () {
        $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');
        $blockEntryType = ImportFixtures::blockEntryType('blockEt', [$plainTextField], 'Block ET');
        $matrixField = ImportFixtures::matrixField('myMatrix', [$blockEntryType], 'My Matrix');

        $seed = ImportFixtures::seedEntry(
            [CustomField::make($matrixField->handle)],
            ['name' => 'With Matrix', 'handle' => 'withMatrix'],
            entryAttrs: ['title' => 'matrix entry', 'slug' => 'matrix-entry'],
        );

        $this->matrixSection = $seed->section;
        $this->matrixEntryType = $seed->entryType;

        $this->matrixImporter = EntryImporter::create()
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
