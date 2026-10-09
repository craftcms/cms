<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $this->import = app(Import::class);

    $plainTextField = ImportFixtures::plainTextField('plainText', 'Plain Text');

    $firstEntryTypeForMatrix = ImportFixtures::blockEntryType('firstEt', [$plainTextField], 'First ET');
    $secondEntryTypeForMatrix = ImportFixtures::blockEntryType('secondEt', [$plainTextField], 'Second ET');

    $nestedMatrixField = ImportFixtures::matrixField('myNestedMatrix', [$firstEntryTypeForMatrix, $secondEntryTypeForMatrix], 'My Nested Matrix');

    $thirdEntryTypeForMatrix = EntryType::factory()
        ->withFieldLayout(
            FieldLayout::factory()
                ->withContentTab([
                    new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                    CustomField::make($plainTextField->handle),
                    CustomField::make($nestedMatrixField->handle),
                ])
                ->create()
        )
        ->create([
            'name' => 'Third ET',
            'handle' => 'thirdEt',
            'hasTitleField' => true,
        ]);

    $this->matrixField = ImportFixtures::matrixField('myMatrix', [$firstEntryTypeForMatrix, $secondEntryTypeForMatrix, $thirdEntryTypeForMatrix], 'My Matrix');

    $seed = ImportFixtures::seedEntry(
        [CustomField::make($this->matrixField->handle)],
        ['name' => 'With Matrix Field', 'handle' => 'withMatrixField'],
    );

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    $this->importer = EntryImporter::create()
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->importWithConfigCriteria = fn ($importer, array $data) => ImportFixtures::importWithConfigCriteria($this->import, $importer, $data);

    $this->importerMatchingSecondEtBlocks = fn () => (clone $this->importer)->matchCriteria([
        'title' => 'title',
        'myMatrix' => [
            'secondEt' => ['title' => 'title'],
        ],
    ]);

    $this->entryData = fn (array $blocks) => [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myMatrix' => $blocks,
        'matchCriteria' => ['title' => 'title'],
    ];
});

it('imports an entry with a matrix field', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'block 1',
            'fields' => ['plainText' => 'foo'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->not()->toBeNull();
    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
});

it('imports multiple blocks of different entry types', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        ['type' => 'secondEt', 'title' => 'block 1', 'fields' => ['plainText' => 'foo']],
        ['type' => 'firstEt', 'title' => 'block 2', 'fields' => ['plainText' => 'bar']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $blocks = $entry->getFieldValue('myMatrix')->all();

    expect($blocks)->toHaveCount(2)
        ->and(array_map(fn ($block) => $block->title, $blocks))->toBe(['block 1', 'block 2'])
        ->and(array_map(fn ($block) => $block->getType()->handle, $blocks))->toBe(['secondEt', 'firstEt']);
});

it('maps block field values and title correctly', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        ['type' => 'secondEt', 'title' => 'block 1', 'fields' => ['plainText' => 'foo']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $block = $entry->getFieldValue('myMatrix')->one();

    expect($block->title)->toBe('block 1');
    expect($block->getFieldValue('plainText'))->toBe('foo');
});

it('updates an existing block when match criteria matches', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $this->import->importItem($importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'block 1',
            'matchCriteria' => ['title' => 'title'],
            'fields' => ['plainText' => 'foo'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    $blockId = $entry->getFieldValue('myMatrix')->one()->id;

    $this->import->importItem($importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'block 1',
            'matchCriteria' => ['title' => 'title'],
            'fields' => ['plainText' => 'updated foo'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $block = $entry->getFieldValue('myMatrix')->one();
    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    // No config criteria for myMatrix, so only the block's inline matchCriteria can match it.
    expect($block->id)->toBe($blockId);
    expect($block->getFieldValue('plainText'))->toBe('updated foo');
});

it('resolves match criteria for nested blocks from the importer config, without it being inlined in the data', function () {
    $importer = ($this->importerMatchingSecondEtBlocks)();

    ($this->importWithConfigCriteria)($importer, ($this->entryData)([
        ['type' => 'secondEt', 'title' => 'block 1', 'fields' => ['plainText' => 'foo']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    $blockId = $entry->getFieldValue('myMatrix')->one()->id;

    ($this->importWithConfigCriteria)($importer, ($this->entryData)([
        ['type' => 'secondEt', 'title' => 'block 1', 'fields' => ['plainText' => 'updated foo']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $block = $entry->getFieldValue('myMatrix')->one();

    expect($entry->getFieldValue('myMatrix')->count())->toBe(1)
        ->and($block->id)->toBe($blockId)
        ->and($block->getFieldValue('plainText'))->toBe('updated foo');
});

it('adds a new block alongside a matched one when a second row is imported', function () {
    $importer = ($this->importerMatchingSecondEtBlocks)();

    ($this->importWithConfigCriteria)($importer, ($this->entryData)([
        ['type' => 'secondEt', 'title' => 'block 1', 'fields' => ['plainText' => 'foo']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $blockId = $entry->getFieldValue('myMatrix')->one()->id;

    ($this->importWithConfigCriteria)($importer, ($this->entryData)([
        ['type' => 'secondEt', 'title' => 'block 1', 'fields' => ['plainText' => 'foo']],
        ['type' => 'secondEt', 'title' => 'block 2', 'fields' => ['plainText' => 'bar']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $blocks = $entry->getFieldValue('myMatrix')->all();

    expect($blocks)->toHaveCount(2)
        ->and(array_map(fn ($block) => $block->title, $blocks))->toBe(['block 1', 'block 2'])
        ->and($blocks[0]->id)->toBe($blockId);
});

it('resolves inline pointer-style match criteria against the block\'s own field value', function () {
    $importer = (clone $this->importer)->matchCriteria([
        'title' => 'title',
        'myMatrix' => [
            'secondEt' => [],
        ],
    ]);

    $this->import->importItem($importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'block 1',
            'matchCriteria' => ['plainText' => 'plainText'],
            'fields' => ['plainText' => 'foo'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    $blockId = $entry->getFieldValue('myMatrix')->one()->id;

    $this->import->importItem($importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'different title',
            'matchCriteria' => ['plainText' => 'plainText'],
            'fields' => ['plainText' => 'foo'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $block = $entry->getFieldValue('myMatrix')->one();

    expect($entry->getFieldValue('myMatrix')->count())->toBe(1)
        ->and($block->id)->toBe($blockId)
        ->and($block->title)->toBe('different title');
});

it('creates a new block when match criteria does not match any existing block', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $this->import->importItem($importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'block 1',
            'matchCriteria' => ['title' => 'title'],
            'fields' => ['plainText' => 'foo'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    $blockId = $entry->getFieldValue('myMatrix')->one()->id;

    $this->import->importItem($importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'block 1',
            'matchCriteria' => ['title' => 'title'],
            'fields' => ['plainText' => 'foo'],
        ],
        [
            'type' => 'secondEt',
            'title' => 'block 2',
            'matchCriteria' => ['title' => 'title'],
            'fields' => ['plainText' => 'bar'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $blocks = $entry->getFieldValue('myMatrix')->all();

    expect($blocks)->toHaveCount(2)
        ->and(array_map(fn ($block) => $block->title, $blocks))->toBe(['block 1', 'block 2'])
        ->and($blocks[0]->id)->toBe($blockId);
});

describe('nested matrix', function () {
    it('imports an entry with a matrix field inside a matrix field', function () {
        $this->import->importItem($this->importer, ($this->entryData)([
            [
                'type' => 'thirdEt',
                'title' => 'outer block 1',
                'fields' => [
                    'plainText' => 'outer text',
                    'myNestedMatrix' => [
                        ['type' => 'firstEt', 'title' => 'inner block 1', 'fields' => ['plainText' => 'nested foo']],
                    ],
                ],
            ],
        ]));

        $entry = EntryElement::find()->title('imported entry')->one();

        expect($entry)->not()->toBeNull();

        $outerBlocks = $entry->getFieldValue('myMatrix');
        expect($outerBlocks->count())->toBe(1);

        $outerBlock = $outerBlocks->one();
        expect($outerBlock->title)->toBe('outer block 1');
        expect($outerBlock->getFieldValue('plainText'))->toBe('outer text');

        $innerBlocks = $outerBlock->getFieldValue('myNestedMatrix');
        expect($innerBlocks->count())->toBe(1);
        expect($innerBlocks->one()->getFieldValue('plainText'))->toBe('nested foo');
    });

    it('imports multiple nested blocks of different entry types', function () {
        $this->import->importItem($this->importer, ($this->entryData)([
            [
                'type' => 'thirdEt',
                'title' => 'outer block 1',
                'fields' => [
                    'plainText' => 'outer text',
                    'myNestedMatrix' => [
                        ['type' => 'firstEt', 'title' => 'inner block 1', 'fields' => ['plainText' => 'nested foo']],
                        ['type' => 'secondEt', 'title' => 'inner block 2', 'fields' => ['plainText' => 'nested bar']],
                    ],
                ],
            ],
        ]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $outerBlock = $entry->getFieldValue('myMatrix')->one();

        expect($outerBlock->getFieldValue('myNestedMatrix')->count())->toBe(2);
    });

    it('updates an existing nested block when match criteria matches', function () {
        $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

        $outerBlock = [
            'type' => 'thirdEt',
            'title' => 'outer block 1',
            'matchCriteria' => ['title' => 'title'],
            'fields' => [
                'plainText' => 'outer text',
                'myNestedMatrix' => [
                    [
                        'type' => 'firstEt',
                        'title' => 'inner block 1',
                        'matchCriteria' => ['title' => 'title'],
                        'fields' => ['plainText' => 'nested foo'],
                    ],
                ],
            ],
        ];

        $this->import->importItem($importer, ($this->entryData)([$outerBlock]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $outerBlockBefore = $entry->getFieldValue('myMatrix')->one();
        expect($outerBlockBefore->getFieldValue('myNestedMatrix')->count())->toBe(1);
        $outerBlockId = $outerBlockBefore->id;
        $innerBlockId = $outerBlockBefore->getFieldValue('myNestedMatrix')->one()->id;

        $outerBlock['fields']['myNestedMatrix'][0]['fields']['plainText'] = 'updated nested foo';

        $this->import->importItem($importer, ($this->entryData)([$outerBlock]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $outerBlockAfter = $entry->getFieldValue('myMatrix')->one();
        $innerBlocks = $outerBlockAfter->getFieldValue('myNestedMatrix');
        // Only inline matchCriteria at both levels, no importer-config entries.
        expect($outerBlockAfter->id)->toBe($outerBlockId);
        expect($innerBlocks->count())->toBe(1)
            ->and($innerBlocks->one()->id)->toBe($innerBlockId)
            ->and($innerBlocks->one()->getFieldValue('plainText'))->toBe('updated nested foo');
    });

    it('resolves match criteria for nested blocks inside a nested block from the importer config', function () {
        $importer = (clone $this->importer)->matchCriteria([
            'title' => 'title',
            'myMatrix' => [
                'thirdEt' => [
                    'title' => 'title',
                    'fields' => [
                        'myNestedMatrix' => [
                            'firstEt' => ['title' => 'title'],
                        ],
                    ],
                ],
            ],
        ]);

        $outerBlock = [
            'type' => 'thirdEt',
            'title' => 'outer block 1',
            'fields' => [
                'plainText' => 'outer text',
                'myNestedMatrix' => [
                    ['type' => 'firstEt', 'title' => 'inner block 1', 'fields' => ['plainText' => 'nested foo']],
                ],
            ],
        ];

        ($this->importWithConfigCriteria)($importer, ($this->entryData)([$outerBlock]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $innerBlock = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix')->one();
        expect($entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix')->count())->toBe(1);
        $innerBlockId = $innerBlock->id;

        $outerBlock['fields']['myNestedMatrix'][0]['fields']['plainText'] = 'updated nested foo';

        ($this->importWithConfigCriteria)($importer, ($this->entryData)([$outerBlock]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $innerBlocks = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix');
        expect($innerBlocks->count())->toBe(1)
            ->and($innerBlocks->one()->id)->toBe($innerBlockId)
            ->and($innerBlocks->one()->getFieldValue('plainText'))->toBe('updated nested foo');
    });

    it('resolves inline pointer-style match criteria for a doubly-nested block', function () {
        $importer = (clone $this->importer)->matchCriteria([
            'title' => 'title',
            'myMatrix' => [
                'thirdEt' => [
                    'title' => 'title',
                    'fields' => [
                        'myNestedMatrix' => [
                            'firstEt' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $outerBlock = [
            'type' => 'thirdEt',
            'title' => 'outer block 1',
            // The outer block must match too, or its nested blocks get a new owner.
            'matchCriteria' => ['title' => 'title'],
            'fields' => [
                'plainText' => 'outer text',
                'myNestedMatrix' => [
                    [
                        'type' => 'firstEt',
                        'title' => 'inner block 1',
                        'matchCriteria' => ['plainText' => 'plainText'],
                        'fields' => ['plainText' => 'nested foo'],
                    ],
                ],
            ],
        ];

        $this->import->importItem($importer, ($this->entryData)([$outerBlock]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $innerBlocks = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix');
        expect($innerBlocks->count())->toBe(1);
        $innerBlockId = $innerBlocks->one()->id;

        $outerBlock['fields']['myNestedMatrix'][0]['title'] = 'renamed inner block';

        $this->import->importItem($importer, ($this->entryData)([$outerBlock]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $innerBlocks = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix');

        expect($innerBlocks->count())->toBe(1)
            ->and($innerBlocks->one()->id)->toBe($innerBlockId)
            ->and($innerBlocks->one()->title)->toBe('renamed inner block');
    });

    it('creates a new nested block when match criteria does not match any existing nested block', function () {
        $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

        $this->import->importItem($importer, ($this->entryData)([
            [
                'type' => 'thirdEt',
                'title' => 'outer block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => [
                    'plainText' => 'outer text',
                    'myNestedMatrix' => [
                        [
                            'type' => 'firstEt',
                            'title' => 'inner block 1',
                            'matchCriteria' => ['title' => 'title'],
                            'fields' => ['plainText' => 'nested foo'],
                        ],
                    ],
                ],
            ],
        ]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $innerBlocks = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix');
        expect($innerBlocks->count())->toBe(1);
        $innerBlockId = $innerBlocks->one()->id;

        $this->import->importItem($importer, ($this->entryData)([
            [
                'type' => 'thirdEt',
                'title' => 'outer block 1',
                'matchCriteria' => ['title' => 'title'],
                'fields' => [
                    'plainText' => 'outer text',
                    'myNestedMatrix' => [
                        [
                            'type' => 'firstEt',
                            'title' => 'inner block 1',
                            'matchCriteria' => ['title' => 'title'],
                            'fields' => ['plainText' => 'nested foo'],
                        ],
                        [
                            'type' => 'secondEt',
                            'title' => 'inner block 2',
                            'matchCriteria' => ['title' => 'title'],
                            'fields' => ['plainText' => 'nested bar'],
                        ],
                    ],
                ],
            ],
        ]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $innerBlocks = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix')->all();

        expect($innerBlocks)->toHaveCount(2)
            ->and(array_map(fn ($block) => $block->title, $innerBlocks))->toBe(['inner block 1', 'inner block 2'])
            ->and(array_map(fn ($block) => $block->getType()->handle, $innerBlocks))->toBe(['firstEt', 'secondEt'])
            ->and($innerBlocks[0]->id)->toBe($innerBlockId);
    });
});

it('skips blocks that are missing a type', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        ['title' => 'no type block', 'fields' => ['plainText' => 'foo']],
        ['type' => 'secondEt', 'title' => 'valid block', 'fields' => ['plainText' => 'bar']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    expect($entry->getFieldValue('myMatrix')->one()->title)->toBe('valid block');
});

it('skips blocks with a type not allowed by the field', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        ['type' => 'notAnEntryType', 'title' => 'bad block', 'fields' => ['plainText' => 'foo']],
        ['type' => 'secondEt', 'title' => 'valid block', 'fields' => ['plainText' => 'bar']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    expect($entry->getFieldValue('myMatrix')->one()->title)->toBe('valid block');
});

it('accepts the sortOrder/entries keyed input format', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'sortOrder' => ['new:1', 'new:2'],
        'entries' => [
            'new:1' => ['type' => 'secondEt', 'title' => 'block 1', 'fields' => ['plainText' => 'foo']],
            'new:2' => ['type' => 'firstEt', 'title' => 'block 2', 'fields' => ['plainText' => 'bar']],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myMatrix')->count())->toBe(2);
});

it('orders keyed entries by the given sortOrder', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'sortOrder' => ['b', 'a'],
        'entries' => [
            'a' => ['type' => 'secondEt', 'title' => 'block a', 'fields' => ['plainText' => 'foo']],
            'b' => ['type' => 'firstEt', 'title' => 'block b', 'fields' => ['plainText' => 'bar']],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myMatrix')->all())->sequence(
        fn ($block) => $block->title->toBe('block b'),
        fn ($block) => $block->title->toBe('block a'),
    );
});

describe('raw grouped/flat data through the full pipeline', function () {
    it('imports raw data end-to-end, matching and clearing correctly', function (array $map, callable $makeRawData) {
        $importer = (clone $this->importer)
            ->matchCriteria([
                'title' => 'title',
                'myMatrix' => [
                    'secondEt' => ['title' => 'title'],
                    'firstEt' => ['title' => 'title'],
                ],
            ])
            ->clearableItems([
                'myMatrix' => [
                    'secondEt' => ['fields' => ['plainText' => true]],
                    'firstEt' => ['fields' => ['plainText' => true]],
                ],
            ]);

        $rawData = $makeRawData($this->section->handle, $this->entryType->handle, 'foo');

        $this->import->importItem(
            $importer,
            ImportHelper::remapData($map, $rawData),
            ImportHelper::normalizeMatchCriteriaFromImporterConfig($importer),
        );

        $entry = EntryElement::find()->title('imported entry')->one();
        expect($entry->getFieldValue('myMatrix')->count())->toBe(2);

        $block1 = $entry->getFieldValue('myMatrix')->status(null)->title('block 1')->one();
        $block1Id = $block1->id;
        expect($block1->getFieldValue('plainText'))->toBe('foo');

        $rawData = $makeRawData($this->section->handle, $this->entryType->handle, null);

        $this->import->importItem(
            $importer,
            ImportHelper::remapData($map, $rawData),
            ImportHelper::normalizeMatchCriteriaFromImporterConfig($importer),
        );

        $entry = EntryElement::find()->title('imported entry')->one();
        expect($entry->getFieldValue('myMatrix')->count())->toBe(2);
        $block1 = $entry->getFieldValue('myMatrix')->status(null)->title('block 1')->one();
        expect($block1->id)->toBe($block1Id);
        expect($block1->getFieldValue('plainText'))->toBeNull();
    })->with([
        'grouped-by-type' => [
            [
                'title' => 'title',
                'sectionId' => 'sectionId',
                'typeId' => 'typeId',
                'myMatrix' => [
                    'secondEt' => [
                        'title' => 'myMatrix.secondEt.title',
                        'fields' => ['plainText' => 'myMatrix.secondEt.plainText'],
                    ],
                    'firstEt' => [
                        'title' => 'myMatrix.firstEt.title',
                        'fields' => ['plainText' => 'myMatrix.firstEt.plainText'],
                    ],
                ],
            ],
            fn (string $sectionHandle, string $entryTypeHandle, ?string $block1PlainText) => [
                'title' => 'imported entry',
                'sectionId' => $sectionHandle,
                'typeId' => $entryTypeHandle,
                'myMatrix' => [
                    'secondEt' => [
                        $block1PlainText === null
                            ? ['title' => 'block 1']
                            : ['title' => 'block 1', 'plainText' => $block1PlainText],
                    ],
                    'firstEt' => [
                        ['title' => 'block 2', 'plainText' => 'bar'],
                    ],
                ],
            ],
        ],
        'flat own-type-per-row' => [
            [
                'title' => 'title',
                'sectionId' => 'sectionId',
                'typeId' => 'typeId',
                'myMatrix' => [
                    'secondEt' => [
                        'title' => 'myMatrix.title',
                        'fields' => ['plainText' => 'myMatrix.plainText'],
                    ],
                    'firstEt' => [
                        'title' => 'myMatrix.title',
                        'fields' => ['plainText' => 'myMatrix.plainText'],
                    ],
                ],
            ],
            fn (string $sectionHandle, string $entryTypeHandle, ?string $block1PlainText) => [
                'title' => 'imported entry',
                'sectionId' => $sectionHandle,
                'typeId' => $entryTypeHandle,
                'myMatrix' => [
                    $block1PlainText === null
                        ? ['type' => 'secondEt', 'title' => 'block 1']
                        : ['type' => 'secondEt', 'title' => 'block 1', 'plainText' => $block1PlainText],
                    ['type' => 'firstEt', 'title' => 'block 2', 'plainText' => 'bar'],
                ],
            ],
        ],
    ]);
});
