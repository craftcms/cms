<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Addresses as AddressesField;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\ImportFixtures;

describe('nested matrix pruning', function () {
    beforeEach(function () {
        $this->import = app(Import::class);

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

        $this->matrixImporter = ElementImporter::create()
            ->className(EntryElement::class)
            ->site(Sites::getPrimarySite()->handle)
            ->transformer(null);

        $this->matrixEntryData = fn (array $blocks) => [
            'title' => 'matrix entry',
            'sectionId' => $this->matrixSection->handle,
            'typeId' => $this->matrixEntryType->handle,
            'matchCriteria' => ['title' => 'title'],
            'myMatrix' => $blocks,
        ];

        // seed two blocks
        $this->import->importItem($this->matrixImporter, ($this->matrixEntryData)([
            [
                'type' => 'blockEt',
                'title' => 'block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => ['plainText' => 'one'],
            ],
            [
                'type' => 'blockEt',
                'title' => 'block 2',
                'matchCriteria' => ['title' => 'title'],
                'fields' => ['plainText' => 'two'],
            ],
        ]));

        $entry = EntryElement::find()->title('matrix entry')->one();
        $this->seededBlockIds = $entry->getFieldValue('myMatrix')->ids();

        expect($this->seededBlockIds)->toHaveCount(2);
    });

    it('deletes an existing block missing from a later import by default', function () {
        $this->import->importItem($this->matrixImporter, ($this->matrixEntryData)([
            [
                'type' => 'blockEt',
                'title' => 'block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => ['plainText' => 'one'],
            ],
        ]));

        $entry = EntryElement::find()->title('matrix entry')->one();
        $blocks = $entry->getFieldValue('myMatrix')->all();
        expect($blocks)->toHaveCount(1);
        expect($blocks[0]->title)->toBe('block 1');
        // the surviving block is matched and updated in place, not recreated
        expect($blocks[0]->id)->toBe($this->seededBlockIds[0]);
    });

    it('logs pruned nested elements via ImportLog when pruning happens', function () {
        ImportLog::shouldReceive('info')
            ->once()
            ->withArgs(fn (string $message, array $context) => ! empty($context['prunedElementIds']) && count($context['prunedElementIds']) === 1);

        $this->import->importItem($this->matrixImporter, ($this->matrixEntryData)([
            [
                'type' => 'blockEt',
                'title' => 'block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => ['plainText' => 'one'],
            ],
        ]));
    });

    it('keeps an existing block missing from a later import when the field opts in to keeping missing elements', function () {
        $importer = (clone $this->matrixImporter)->keepMissingNestedElements(['myMatrix' => ['__keep__' => true]]);

        $this->import->importItem($importer, ($this->matrixEntryData)([
            [
                'type' => 'blockEt',
                'title' => 'block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => ['plainText' => 'one'],
            ],
        ]));

        $entry = EntryElement::find()->title('matrix entry')->one();
        $blocks = $entry->getFieldValue('myMatrix')->all();
        expect($blocks)->toHaveCount(2);
    });

    it('does not log anything when the field opts in to keeping missing elements', function () {
        ImportLog::shouldReceive('info')->never();

        $importer = (clone $this->matrixImporter)->keepMissingNestedElements(['myMatrix' => ['__keep__' => true]]);

        $this->import->importItem($importer, ($this->matrixEntryData)([
            [
                'type' => 'blockEt',
                'title' => 'block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => ['plainText' => 'one'],
            ],
        ]));
    });
});

describe('matrix in matrix pruning', function () {
    beforeEach(function () {
        $this->import = app(Import::class);

        $innerPlainTextField = ImportFixtures::plainTextField('innerPlainText', 'Inner Plain Text');
        $innerEntryType = ImportFixtures::blockEntryType('innerEt', [$innerPlainTextField], 'Inner ET');
        $innerMatrixField = ImportFixtures::matrixField('innerMatrix', [$innerEntryType], 'Inner Matrix');

        $outerPlainTextField = ImportFixtures::plainTextField('outerPlainText', 'Outer Plain Text');
        $outerEntryType = ImportFixtures::blockEntryType('outerEt', [$outerPlainTextField, $innerMatrixField], 'Outer ET');
        $outerMatrixField = ImportFixtures::matrixField('outerMatrix', [$outerEntryType], 'Outer Matrix');

        $seed = ImportFixtures::seedEntry(
            [CustomField::make($outerMatrixField->handle)],
            ['name' => 'With Outer Matrix', 'handle' => 'withOuterMatrix'],
            entryAttrs: ['title' => 'matrix in matrix entry', 'slug' => 'matrix-in-matrix-entry'],
        );

        $this->matrixInMatrixSection = $seed->section;
        $this->matrixInMatrixEntryType = $seed->entryType;

        $this->matrixInMatrixImporter = ElementImporter::create()
            ->className(EntryElement::class)
            ->site(Sites::getPrimarySite()->handle)
            ->transformer(null);

        $this->matrixInMatrixEntryData = fn (array $outerBlocks) => [
            'title' => 'matrix in matrix entry',
            'sectionId' => $this->matrixInMatrixSection->handle,
            'typeId' => $this->matrixInMatrixEntryType->handle,
            'matchCriteria' => ['title' => 'title'],
            'outerMatrix' => $outerBlocks,
        ];

        // seed two outer blocks, each with one inner block
        $this->import->importItem($this->matrixInMatrixImporter, ($this->matrixInMatrixEntryData)([
            [
                'type' => 'outerEt',
                'title' => 'outer 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => [
                    'outerPlainText' => 'outer one',
                    'innerMatrix' => [
                        [
                            'type' => 'innerEt',
                            'title' => 'inner 1',
                            'matchCriteria' => ['title' => 'title'],
                            'fields' => ['innerPlainText' => 'inner one'],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'outerEt',
                'title' => 'outer 2',
                'matchCriteria' => ['title' => 'title'],
                'fields' => [
                    'outerPlainText' => 'outer two',
                    'innerMatrix' => [
                        [
                            'type' => 'innerEt',
                            'title' => 'inner 2',
                            'matchCriteria' => ['title' => 'title'],
                            'fields' => ['innerPlainText' => 'inner two'],
                        ],
                    ],
                ],
            ],
        ]));

        $entry = EntryElement::find()->title('matrix in matrix entry')->one();
        expect($entry->getFieldValue('outerMatrix')->count())->toBe(2);
    });

    it('keeps an outer block missing from a later import when only the outer field opts in, while still pruning the remaining block’s missing inner block', function () {
        $importer = (clone $this->matrixInMatrixImporter)
            ->keepMissingNestedElements(['outerMatrix' => ['__keep__' => true]]);

        // omit "outer 2" entirely, and omit "outer 1"'s inner block
        $this->import->importItem($importer, ($this->matrixInMatrixEntryData)([
            [
                'type' => 'outerEt',
                'title' => 'outer 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => [
                    'outerPlainText' => 'outer one',
                    'innerMatrix' => [],
                ],
            ],
        ]));

        $entry = EntryElement::find()->title('matrix in matrix entry')->one();
        $outerBlocks = $entry->getFieldValue('outerMatrix')->all();
        expect($outerBlocks)->toHaveCount(2);

        $outerOne = collect($outerBlocks)->firstWhere('title', 'outer 1');
        expect($outerOne->getFieldValue('innerMatrix')->count())->toBe(0);
    });

    it('prunes an outer block missing from a later import when only the inner field opts in, while keeping the remaining block’s missing inner block', function () {
        $importer = (clone $this->matrixInMatrixImporter)
            ->keepMissingNestedElements([
                'outerMatrix' => [
                    'outerEt' => [
                        'fields' => [
                            'innerMatrix' => ['__keep__' => true],
                        ],
                    ],
                ],
            ]);

        // omit "outer 2" entirely, and omit "outer 1"'s inner block
        $this->import->importItem($importer, ($this->matrixInMatrixEntryData)([
            [
                'type' => 'outerEt',
                'title' => 'outer 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => [
                    'outerPlainText' => 'outer one',
                    'innerMatrix' => [],
                ],
            ],
        ]));

        $entry = EntryElement::find()->title('matrix in matrix entry')->one();
        $outerBlocks = $entry->getFieldValue('outerMatrix')->all();
        expect($outerBlocks)->toHaveCount(1);

        $outerOne = $outerBlocks[0];
        expect($outerOne->title)->toBe('outer 1');
        expect($outerOne->getFieldValue('innerMatrix')->count())->toBe(1);
    });
});

// Addresses::canKeepMissingNestedElements() is true (asserted in the unit test), but only matrix
// fields exercised it until now.
describe('addresses pruning', function () {
    beforeEach(function () {
        $this->import = app(Import::class);

        $addressesField = Field::factory()->create([
            'name' => 'My Addresses',
            'handle' => 'myAddresses',
            'type' => AddressesField::class,
        ]);

        Fields::refreshFields();

        $seed = ImportFixtures::seedEntry(
            [CustomField::make($addressesField->handle)],
            ['name' => 'With Addresses', 'handle' => 'withAddresses'],
            entryAttrs: ['title' => 'addresses entry', 'slug' => 'addresses-entry'],
        );

        $this->section = $seed->section;
        $this->entryType = $seed->entryType;

        $this->importer = ElementImporter::create()
            ->className(EntryElement::class)
            ->site(Sites::getPrimarySite()->handle)
            ->matchCriteria(['title' => 'title'])
            ->transformer(null);

        $this->address = fn (string $title, string $line1) => [
            'title' => $title,
            'matchCriteria' => ['title' => 'title'],
            'countryCode' => 'US',
            'addressLine1' => $line1,
            'locality' => 'My Town',
            'administrativeArea' => 'UT',
            'postalCode' => '12345',
        ];

        $this->entryData = fn (array $addresses) => [
            'title' => 'addresses entry',
            'sectionId' => $this->section->handle,
            'typeId' => $this->entryType->handle,
            'matchCriteria' => ['title' => 'title'],
            'myAddresses' => $addresses,
        ];

        // seed two addresses
        $this->import->importItem($this->importer, ($this->entryData)([
            ($this->address)('address 1', '1 First St'),
            ($this->address)('address 2', '2 Second St'),
        ]));

        $seeded = Address::find()
            ->ownerId(EntryElement::find()->title('addresses entry')->one()->id)
            ->all();

        // ids() order isn't tied to the order the addresses were imported in, so index them by label
        $this->seededAddressIds = array_map(fn (Address $address) => $address->id, $seeded);
        $this->seededAddressIdByTitle = array_column(
            array_map(fn (Address $address) => ['title' => $address->title, 'id' => $address->id], $seeded),
            'id',
            'title',
        );

        expect($this->seededAddressIds)->toHaveCount(2);
    });

    it('deletes an existing address missing from a later import by default', function () {
        $this->import->importItem($this->importer, ($this->entryData)([
            ($this->address)('address 1', '1 First St'),
        ]));

        $entry = EntryElement::find()->title('addresses entry')->one();
        $addresses = Address::find()->ownerId($entry->id)->all();

        expect($addresses)->toHaveCount(1)
            ->and($addresses[0]->title)->toBe('address 1');
    });

    it('matches the supplied address rather than recreating it while pruning the missing one', function () {
        $this->import->importItem($this->importer, ($this->entryData)([
            ($this->address)('address 1', '1 First St'),
        ]));

        $entry = EntryElement::find()->title('addresses entry')->one();

        expect(Address::find()->ownerId($entry->id)->ids())->toBe([$this->seededAddressIdByTitle['address 1']]);
    });

    it('keeps an existing address missing from a later import when the field opts in to keeping missing elements', function () {
        $importer = (clone $this->importer)->keepMissingNestedElements(['myAddresses' => ['__keep__' => true]]);

        $this->import->importItem($importer, ($this->entryData)([
            ($this->address)('address 1', '1 First St'),
        ]));

        $entry = EntryElement::find()->title('addresses entry')->one();

        expect(Address::find()->ownerId($entry->id)->ids())->toEqualCanonicalizing($this->seededAddressIds);
    });
});

// A matrix nested inside a content block: the container that opts in to keeping missing elements is
// the matrix, but it's reached through the content block, so the keepMissingNestedElements key has
// to line up with the path ElementImporter::collectAndEnableKeepFields() walks.
describe('matrix inside a content block pruning', function () {
    beforeEach(function () {
        $this->import = app(Import::class);

        $blockTextField = ImportFixtures::plainTextField('blockText', 'Block Text');
        $blockEntryType = ImportFixtures::blockEntryType('cbBlockEt', [$blockTextField], 'CB Block ET');
        $cbMatrixField = ImportFixtures::matrixField('cbMatrix', [$blockEntryType], 'CB Matrix');

        Fields::refreshFields();

        $contentBlockField = Field::factory()->create([
            'name' => 'My Content Block',
            'handle' => 'myContentBlock',
            'type' => ContentBlockField::class,
            'settings' => [
                'fieldLayouts' => [
                    Str::uuid()->toString() => [
                        'tabs' => [[
                            'uid' => Str::uuid()->toString(),
                            'name' => 'Content',
                            'elements' => [[
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $cbMatrixField->uid,
                                'required' => false,
                            ]],
                        ]],
                    ],
                ],
            ],
        ]);

        Fields::refreshFields();

        $seed = ImportFixtures::seedEntry(
            [CustomField::make($contentBlockField->handle)],
            ['name' => 'With Content Block', 'handle' => 'withContentBlock'],
            entryAttrs: ['title' => 'cb entry', 'slug' => 'cb-entry'],
        );

        $this->section = $seed->section;
        $this->entryType = $seed->entryType;

        $this->importer = ElementImporter::create()
            ->className(EntryElement::class)
            ->site(Sites::getPrimarySite()->handle)
            ->matchCriteria(['title' => 'title'])
            ->transformer(null);

        $this->block = fn (string $title, string $text) => [
            'type' => 'cbBlockEt',
            'title' => $title,
            'matchCriteria' => ['title' => 'title'],
            'fields' => ['blockText' => $text],
        ];

        $this->entryData = fn (array $blocks) => [
            'title' => 'cb entry',
            'sectionId' => $this->section->handle,
            'typeId' => $this->entryType->handle,
            'matchCriteria' => ['title' => 'title'],
            'myContentBlock' => ['fields' => ['cbMatrix' => $blocks]],
        ];

        // seed two blocks inside the content block's matrix
        $this->import->importItem($this->importer, ($this->entryData)([
            ($this->block)('block 1', 'one'),
            ($this->block)('block 2', 'two'),
        ]));

        $this->nestedBlocks = fn () => EntryElement::find()->title('cb entry')->one()
            ->getFieldValue('myContentBlock')->getFieldValue('cbMatrix');

        $this->seededBlockIds = ($this->nestedBlocks)()->ids();

        expect($this->seededBlockIds)->toHaveCount(2);
    });

    it('deletes a nested block missing from a later import by default', function () {
        $this->import->importItem($this->importer, ($this->entryData)([
            ($this->block)('block 1', 'one'),
        ]));

        expect(($this->nestedBlocks)()->ids())->toBe([$this->seededBlockIds[0]]);
    });

    // The mapping UI names this input keepMissingNestedElements[myContentBlock][fields][cbMatrix][__keep__],
    // so that's the shape a saved config carries.
    it('keeps a nested block missing from a later import when the nested matrix opts in', function () {
        $importer = (clone $this->importer)->keepMissingNestedElements([
            'myContentBlock' => ['fields' => ['cbMatrix' => ['__keep__' => true]]],
        ]);

        $this->import->importItem($importer, ($this->entryData)([
            ($this->block)('block 1', 'one'),
        ]));

        expect(($this->nestedBlocks)()->ids())->toEqualCanonicalizing($this->seededBlockIds);
    });
});
