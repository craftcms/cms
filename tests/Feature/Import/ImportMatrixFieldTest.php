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
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;

beforeEach(function () {
    $this->import = app(Import::class);

    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);

    $firstEntryTypeForMatrix = EntryType::factory()
        ->withField($plainTextField)
        ->create([
            'name' => 'First ET',
            'handle' => 'firstEt',
            'hasTitleField' => true,
        ]);

    $secondEntryTypeForMatrix = EntryType::factory()
        ->withField($plainTextField)
        ->create([
            'name' => 'Second ET',
            'handle' => 'secondEt',
            'hasTitleField' => true,
        ]);

    $nestedMatrixField = Field::factory()->create([
        'name' => 'My Nested Matrix',
        'handle' => 'myNestedMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$firstEntryTypeForMatrix->id, $secondEntryTypeForMatrix->id]],
    ]);

    $thirdEntryTypeForMatrix = EntryType::factory()
        ->withFieldLayout(
            FieldLayout::factory()
                ->withContentTab([
                    ['uid' => Str::uuid()->toString(), 'type' => EntryTitleField::class, 'required' => true],
                    ['uid' => Str::uuid()->toString(), 'type' => CustomField::class, 'fieldUid' => $plainTextField->uid, 'required' => false],
                    ['uid' => Str::uuid()->toString(), 'type' => CustomField::class, 'fieldUid' => $nestedMatrixField->uid, 'required' => false],
                ])
                ->create()
        )
        ->create([
            'name' => 'Third ET',
            'handle' => 'thirdEt',
            'hasTitleField' => true,
        ]);

    $this->matrixField = Field::factory()->create([
        'name' => 'My Matrix',
        'handle' => 'myMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$firstEntryTypeForMatrix->id, $secondEntryTypeForMatrix->id, $thirdEntryTypeForMatrix->id]],
    ]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $fieldLayout = FieldLayout::factory()
        ->withContentTab([
            [
                'uid' => Str::uuid()->toString(),
                'type' => EntryTitleField::class,
                'required' => true,
            ],
            [
                'uid' => Str::uuid()->toString(),
                'type' => CustomField::class,
                'fieldUid' => $this->matrixField->uid,
                'required' => false,
            ],
        ])
        ->create();

    $entryType = EntryType::factory()
        ->withFieldLayout($fieldLayout)
        ->create([
            'name' => 'With Matrix Field',
            'handle' => 'withMatrixField',
            'hasTitleField' => true,
        ]);

    $section = Section::factory()->withEntryTypes($entryType)->create([
        'type' => SectionType::Channel,
    ]);

    $result = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withFieldLayout($fieldLayout)
        ->createElementWithFields([
            'title' => 'seed entry',
            'slug' => 'seed-entry',
        ]);

    $this->section = $result->element->getSection();
    $this->entryType = $result->element->getType();

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->entryData = fn (array $blocks) => [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myMatrix' => $blocks,
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

    expect($entry->getFieldValue('myMatrix')->count())->toBe(2);
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

    $this->import->importItem($importer, ($this->entryData)([
        [
            'type' => 'secondEt',
            'title' => 'block 1',
            'matchCriteria' => ['title' => 'title'],
            'fields' => ['plainText' => 'updated foo'],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myMatrix')->count())->toBe(1);
    expect($entry->getFieldValue('myMatrix')->one()->getFieldValue('plainText'))->toBe('updated foo');
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
        expect($entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix')->count())->toBe(1);

        $outerBlock['fields']['myNestedMatrix'][0]['fields']['plainText'] = 'updated nested foo';

        $this->import->importItem($importer, ($this->entryData)([$outerBlock]));

        $entry = EntryElement::find()->title('imported entry')->one();
        $innerBlocks = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myNestedMatrix');
        expect($innerBlocks->count())->toBe(1)
            ->and($innerBlocks->one()->getFieldValue('plainText'))->toBe('updated nested foo');
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
