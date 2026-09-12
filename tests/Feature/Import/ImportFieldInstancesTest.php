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
    $unusedField = ImportFixtures::plainTextField('notInThisLayout', 'Not In This Layout');
    $blockEntryType = ImportFixtures::blockEntryType('blockEt', [$plainTextField], 'Block ET');
    $matrixField = ImportFixtures::matrixField('notInThisLayoutEither', [$blockEntryType], 'Not In This Layout Either');

    // two instances of the same field in one layout, the second one re-handled - the shape the
    // manual fixtures use for plainText/plainText2
    $this->twoInstancesType = ImportFixtures::entryTypeWithTitle(
        [
            CustomField::make($plainTextField->handle),
            CustomField::make($plainTextField->handle)->handle('plainText2')->label('Plain Text 2'),
        ],
        ['name' => 'Two Instances', 'handle' => 'twoInstances'],
    );

    // the same two instances in the opposite layout order
    $this->reversedInstancesType = ImportFixtures::entryTypeWithTitle(
        [
            CustomField::make($plainTextField->handle)->handle('plainText2')->label('Plain Text 2'),
            CustomField::make($plainTextField->handle),
        ],
        ['name' => 'Reversed Instances', 'handle' => 'reversedInstances'],
    );

    $this->section = Section::factory()
        ->withEntryTypes($this->twoInstancesType, $this->reversedInstancesType)
        ->create(['minAuthors' => 0]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->unusedFieldHandle = $unusedField->handle;
    $this->unusedMatrixHandle = $matrixField->handle;

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null)
        ->matchCriteria(['title' => 'title']);

    $this->entryData = fn (string $typeHandle, array $values = []) => array_merge([
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $typeHandle,
        'matchCriteria' => ['title' => 'title'],
    ], $values);

    $this->importedEntry = fn () => EntryElement::find()->title('imported entry')->status(null)->one();
});

it('imports both instances of the same field in one layout', function () {
    $this->import->importItem($this->importer, ($this->entryData)('twoInstances', [
        'plainText' => 'first instance',
        'plainText2' => 'second instance',
    ]));

    $entry = ($this->importedEntry)();

    expect($entry->getFieldValue('plainText'))->toBe('first instance')
        ->and($entry->getFieldValue('plainText2'))->toBe('second instance');
});

it('imports both instances when their layout order is reversed', function () {
    $this->import->importItem($this->importer, ($this->entryData)('reversedInstances', [
        'plainText' => 'first instance',
        'plainText2' => 'second instance',
    ]));

    $entry = ($this->importedEntry)();

    expect($entry->getFieldValue('plainText'))->toBe('first instance')
        ->and($entry->getFieldValue('plainText2'))->toBe('second instance');
});

it('imports one instance without touching the other', function () {
    $this->import->importItem($this->importer, ($this->entryData)('twoInstances', [
        'plainText2' => 'only the second instance',
    ]));

    $entry = ($this->importedEntry)();

    expect($entry->getFieldValue('plainText2'))->toBe('only the second instance')
        ->and($entry->getFieldValue('plainText'))->toBeNull();
});

// the manual fixtures rely on this: rows carry a plainText value for entry types whose layout has
// no plain text field at all ("this value should be ignored")
it('ignores a value for a field that is not in the entry type’s layout', function () {
    $this->import->importItem($this->importer, ($this->entryData)('twoInstances', [
        'plainText' => 'kept',
        $this->unusedFieldHandle => 'dropped',
    ]));

    $entry = ($this->importedEntry)();

    expect($entry)->not->toBeNull()
        ->and($entry->getFieldValue('plainText'))->toBe('kept')
        ->and($entry->getFieldLayout()->getFieldByHandle($this->unusedFieldHandle))->toBeNull();
});

it('ignores a container field’s value when the field is not in the layout, creating no nested elements', function () {
    $this->import->importItem($this->importer, ($this->entryData)('twoInstances', [
        'plainText' => 'kept',
        $this->unusedMatrixHandle => [
            ['type' => 'blockEt', 'title' => 'block 1', 'fields' => ['plainText' => 'nested']],
        ],
    ]));

    $entry = ($this->importedEntry)();

    expect($entry)->not->toBeNull()
        ->and($entry->getFieldValue('plainText'))->toBe('kept')
        ->and(EntryElement::find()->title('block 1')->status(null)->count())->toBe(0);
});
