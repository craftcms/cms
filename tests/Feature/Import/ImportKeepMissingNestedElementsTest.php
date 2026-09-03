<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\ImportLog;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;

it('supports keeping missing nested elements by default for matrix fields', function () {
    expect((new Matrix)->canKeepMissingNestedElements())->toBeTrue();
});

it('supports keeping missing nested elements by default for addresses fields', function () {
    expect((new Addresses)->canKeepMissingNestedElements())->toBeTrue();
});

it('does not support keeping missing nested elements for content block fields', function () {
    expect((new ContentBlock)->canKeepMissingNestedElements())->toBeFalse();
});

it('normalizes a flat list of dot-notation handles into the nested __keep__-leaf shape', function () {
    $importer = ElementImporter::create()->keepMissingNestedElements(['myMatrix', 'some.nested.handle']);

    expect($importer->keepMissingNestedElements)->toBe([
        'myMatrix' => ['__keep__' => true],
        'some' => ['nested' => ['handle' => ['__keep__' => true]]],
    ]);
});

describe('nested matrix pruning', function () {
    beforeEach(function () {
        $this->import = app(Import::class);

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
            ->transformer(null);

        $this->matrixEntryData = fn (array $blocks) => [
            'title' => 'matrix entry',
            'sectionId' => $this->matrixSection->handle,
            'typeId' => $this->matrixEntryType->handle,
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

        $innerPlainTextField = Field::factory()->create([
            'name' => 'Inner Plain Text',
            'handle' => 'innerPlainText',
            'type' => PlainText::class,
        ]);

        $innerEntryType = EntryType::factory()
            ->withField($innerPlainTextField)
            ->create(['name' => 'Inner ET', 'handle' => 'innerEt', 'hasTitleField' => true]);

        $innerMatrixField = Field::factory()->create([
            'name' => 'Inner Matrix',
            'handle' => 'innerMatrix',
            'type' => Matrix::class,
            'settings' => ['entryTypes' => [$innerEntryType->id]],
        ]);

        $outerPlainTextField = Field::factory()->create([
            'name' => 'Outer Plain Text',
            'handle' => 'outerPlainText',
            'type' => PlainText::class,
        ]);

        $outerEntryType = EntryType::factory()
            ->withFieldLayout(
                FieldLayout::factory()
                    ->withContentTab([
                        new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                        CustomField::make($outerPlainTextField->handle),
                        CustomField::make($innerMatrixField->handle),
                    ])
                    ->create()
            )
            ->create(['name' => 'Outer ET', 'handle' => 'outerEt', 'hasTitleField' => true]);

        $outerMatrixField = Field::factory()->create([
            'name' => 'Outer Matrix',
            'handle' => 'outerMatrix',
            'type' => Matrix::class,
            'settings' => ['entryTypes' => [$outerEntryType->id]],
        ]);

        Fields::refreshFields();

        $fieldLayout = FieldLayout::factory()
            ->withContentTab([
                new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                CustomField::make($outerMatrixField->handle),
            ])
            ->create();

        $entryType = EntryType::factory()
            ->withFieldLayout($fieldLayout)
            ->create(['name' => 'With Outer Matrix', 'handle' => 'withOuterMatrix', 'hasTitleField' => true]);

        $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

        $result = Entry::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->withFieldLayout($fieldLayout)
            ->createElementWithFields(['title' => 'matrix in matrix entry', 'slug' => 'matrix-in-matrix-entry']);

        $this->matrixInMatrixSection = $result->element->getSection();
        $this->matrixInMatrixEntryType = $result->element->getType();

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
