<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Sites;
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
            ->matchCriteria(['title' => 'title'])
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
        expect($entry->getFieldValue('myMatrix')->count())->toBe(2);
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
            ->matchCriteria([
                'title' => 'title',
                'outerMatrix' => [
                    'outerEt' => [
                        'title' => 'title',
                        'fields' => [
                            'innerMatrix' => [
                                'innerEt' => ['title' => 'title'],
                            ],
                        ],
                    ],
                ],
            ])
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
