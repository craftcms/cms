<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
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

    // thirdEt has its own title field element (unlike the other block types, which rely on hasTitleField alone).
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

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    // An importer's own matchCriteria only reaches the pipeline when it's resolved and passed into
    // importItem() - which is what Import::import(), the import job and craft:import:element all do.
    // Calling importItem() without it means config-level criteria is silently ignored.
    $this->importWithConfigCriteria = fn ($importer, array $data) => $this->import->importItem(
        $importer,
        $data,
        ImportHelper::normalizeMatchCriteriaFromImporterConfig($importer),
    );

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

    // assert the types too, so this can't pass with both blocks coming out as the same type
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
    // The importer's own matchCriteria config has no entry for the myMatrix container field at all,
    // so this relies entirely on the block's inline matchCriteria being resolved. If it isn't, the
    // block never actually matches and a new one gets created (with the old one discarded) instead
    // of the same block being updated in place, which the id check below would catch.
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

    // the id check is what separates matching from the block being recreated - without it this
    // test passes even when the config criteria never reaches the pipeline at all
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

    // The block's title changes but its plainText value (the inline match criteria) stays the
    // same, so if the pointer resolved correctly this should update the existing block rather
    // than create a new one.
    $this->import->importItem($importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'different title',
            'matchCriteria' => ['plainText' => 'plainText'],
            'fields' => ['plainText' => 'foo'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    expect($entry->getFieldValue('myMatrix')->one()->title)->toBe('different title');
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
    expect($entry->getFieldValue('myMatrix')->count())->toBe(2);
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
        // Neither level has an importer-config matchCriteria entry (only inline matchCriteria is
        // provided), so this only stays as the same rows if both levels' inline matchCriteria
        // actually got resolved and used for matching, rather than a fresh outer/inner block pair
        // being created and the old ones discarded.
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
        expect($entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix')->count())->toBe(1);

        // Title changes but the inline-matched plainText value stays the same, so the existing
        // inner block should be updated (not duplicated) if the pointer resolved correctly.
        $outerBlock['fields']['myNestedMatrix'][0]['title'] = 'renamed inner block';

        $this->import->importItem($importer, ($this->entryData)([$outerBlock]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $innerBlocks = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix');
        expect($innerBlocks->count())->toBe(1)
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
        expect($entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix')->count())->toBe(1);

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
        expect($entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix')->count())->toBe(2);
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
            // we must pass the importer's matchCriteria as this way because it only depends on the importer config,
            // so it's done once per config rather than for each root item that is being imported
            ImportHelper::normalizeMatchCriteriaFromImporterConfig($importer),
        );

        $entry = EntryElement::find()->title('imported entry')->one();
        expect($entry->getFieldValue('myMatrix')->count())->toBe(2);

        $block1 = $entry->getFieldValue('myMatrix')->status(null)->title('block 1')->one();
        $block1Id = $block1->id;
        expect($block1->getFieldValue('plainText'))->toBe('foo');

        // Re-import block 1 with plainText omitted from the source entirely - since it's marked
        // clearable, it should be cleared to null on the SAME block (matched by title), not left
        // untouched on a freshly created duplicate.
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
