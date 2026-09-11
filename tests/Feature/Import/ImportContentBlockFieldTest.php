<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Addresses as AddressesField;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $this->import = app(Import::class);

    $innerField = Field::factory()->create([
        'name' => 'CB Text',
        'handle' => 'cbText',
        'type' => PlainText::class,
    ]);

    $blockTextField = ImportFixtures::plainTextField('blockText', 'Block Text');
    $blockEntryType = ImportFixtures::blockEntryType('cbBlockEt', [$blockTextField], 'CB Block ET');

    // one matrix inside the content block, one alongside it on the entry
    $cbMatrixField = ImportFixtures::matrixField('cbMatrix', [$blockEntryType], 'CB Matrix');
    $entryMatrixField = ImportFixtures::matrixField('myMatrix', [$blockEntryType], 'My Matrix');

    $addressesField = Field::factory()->create([
        'name' => 'My Addresses',
        'handle' => 'myAddresses',
        'type' => AddressesField::class,
    ]);

    Fields::refreshFields();

    $layoutUid = Str::uuid()->toString();
    $contentBlockField = Field::factory()->create([
        'name' => 'My Content Block',
        'handle' => 'myContentBlock',
        'type' => ContentBlockField::class,
        'settings' => [
            'fieldLayouts' => [
                $layoutUid => [
                    'tabs' => [[
                        'uid' => Str::uuid()->toString(),
                        'name' => 'Content',
                        'elements' => [
                            [
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $innerField->uid,
                                'required' => false,
                            ],
                            [
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $cbMatrixField->uid,
                                'required' => false,
                            ],
                        ],
                    ]],
                ],
            ],
        ],
    ]);

    Fields::refreshFields();

    $seed = ImportFixtures::seedEntry(
        [
            CustomField::make($contentBlockField->handle),
            CustomField::make($entryMatrixField->handle),
            CustomField::make($addressesField->handle),
        ],
        ['name' => 'With Content Block Field', 'handle' => 'withContentBlockField'],
    );

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->entryData = fn (?array $contentBlock) => [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myContentBlock' => $contentBlock,
        'matchCriteria' => ['title' => 'title'],
    ];

    // for rows that set more than just the content block
    $this->entryDataWith = fn (array $values) => array_merge([
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'matchCriteria' => ['title' => 'title'],
    ], $values);

    $this->address = [
        'title' => 'address 1',
        'countryCode' => 'US',
        'addressLine1' => '123 Main St',
        'administrativeArea' => 'UT',
        'postalCode' => '12345',
        'locality' => 'My Town',
    ];
});

it('imports an entry with a content block field', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'fields' => ['cbText' => 'foo'],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->not()->toBeNull();
    expect($entry->getFieldValue('myContentBlock'))->not()->toBeNull();
});

it('maps content block field values correctly', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'fields' => ['cbText' => 'foo'],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myContentBlock')->getFieldValue('cbText'))->toBe('foo');
});

it('updates content block field values on re-import', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $this->import->importItem($importer, ($this->entryData)([
        'fields' => ['cbText' => 'foo'],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myContentBlock')->getFieldValue('cbText'))->toBe('foo');

    $this->import->importItem($importer, ($this->entryData)([
        'fields' => ['cbText' => 'updated text'],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry->getFieldValue('myContentBlock')->getFieldValue('cbText'))->toBe('updated text');
});

// The manual fixtures nest a matrix inside a content block (myCb.fields.matrixInner); only the
// reverse (a content block inside a matrix) was covered before.
it('imports a matrix nested inside a content block', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'fields' => [
            'cbText' => 'foo',
            'cbMatrix' => [
                ['type' => 'cbBlockEt', 'title' => 'cb block 1', 'fields' => ['blockText' => 'one']],
                ['type' => 'cbBlockEt', 'title' => 'cb block 2', 'fields' => ['blockText' => 'two']],
            ],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $blocks = $entry->getFieldValue('myContentBlock')->getFieldValue('cbMatrix')->all();

    expect($entry->getFieldValue('myContentBlock')->getFieldValue('cbText'))->toBe('foo')
        ->and($blocks)->toHaveCount(2)
        ->and(array_map(fn ($block) => $block->title, $blocks))->toBe(['cb block 1', 'cb block 2'])
        ->and($blocks[0]->getFieldValue('blockText'))->toBe('one');
});

/** A block for the matrix inside the content block, matched on its own title. */
function cbNestedBlock(string $text): array
{
    return [
        'type' => 'cbBlockEt',
        'title' => 'cb block 1',
        'matchCriteria' => ['title' => 'title'],
        'fields' => ['blockText' => $text],
    ];
}

it('updates the value of a matrix block nested inside a content block on re-import', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);
    $block = cbNestedBlock(...);

    $this->import->importItem($importer, ($this->entryData)([
        'fields' => ['cbText' => 'foo', 'cbMatrix' => [$block('one')]],
    ]));

    $this->import->importItem($importer, ($this->entryData)([
        'fields' => ['cbText' => 'foo', 'cbMatrix' => [$block('updated one')]],
    ]));

    $blocks = EntryElement::find()->title('imported entry')->one()
        ->getFieldValue('myContentBlock')->getFieldValue('cbMatrix');

    expect($blocks->count())->toBe(1)
        ->and($blocks->one()->getFieldValue('blockText'))->toBe('updated one');
});

// The entry and the content block element both keep their ids across the re-import, but the block
// inside the content block doesn't - the same inline matchCriteria one level up (on a matrix
// directly on the entry) does match in place, see ImportMatrixFieldTest's "updates an existing
// block when match criteria matches".
it('updates the same matrix block nested inside a content block in place', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);
    $block = cbNestedBlock(...);

    $this->import->importItem($importer, ($this->entryData)([
        'fields' => ['cbText' => 'foo', 'cbMatrix' => [$block('one')]],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $blockId = $entry->getFieldValue('myContentBlock')->getFieldValue('cbMatrix')->one()->id;

    $this->import->importItem($importer, ($this->entryData)([
        'fields' => ['cbText' => 'foo', 'cbMatrix' => [$block('updated one')]],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry->getFieldValue('myContentBlock')->getFieldValue('cbMatrix')->one()->id)->toBe($blockId);
});

// fixture row 5: a matrix, a content block and an addresses field all set on the same entry
it('imports a matrix, a content block and addresses on one entry', function () {
    $this->import->importItem($this->importer, ($this->entryDataWith)([
        'myMatrix' => [
            ['type' => 'cbBlockEt', 'title' => 'entry block', 'fields' => ['blockText' => 'matrix value']],
        ],
        'myContentBlock' => [
            'fields' => [
                'cbText' => 'content block value',
                'cbMatrix' => [
                    ['type' => 'cbBlockEt', 'title' => 'cb block', 'fields' => ['blockText' => 'nested value']],
                ],
            ],
        ],
        'myAddresses' => [$this->address],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $contentBlock = $entry->getFieldValue('myContentBlock');

    expect($entry->getFieldValue('myMatrix')->count())->toBe(1)
        ->and($entry->getFieldValue('myMatrix')->one()->getFieldValue('blockText'))->toBe('matrix value')
        ->and($contentBlock->getFieldValue('cbText'))->toBe('content block value')
        ->and($contentBlock->getFieldValue('cbMatrix')->one()->getFieldValue('blockText'))->toBe('nested value')
        ->and($entry->getFieldValue('myAddresses')->count())->toBe(1)
        ->and($entry->getFieldValue('myAddresses')->one()->addressLine1)->toBe('123 Main St');
});

// the other shape normalizeValueForImport() documents: field values straight in the top-level array
// rather than wrapped in `fields`
it('imports a content block whose values are given without a fields wrapper', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'cbText' => 'foo',
        'cbMatrix' => [
            ['type' => 'cbBlockEt', 'title' => 'cb block 1', 'fields' => ['blockText' => 'one']],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $contentBlock = $entry->getFieldValue('myContentBlock');

    expect($contentBlock->getFieldValue('cbText'))->toBe('foo')
        ->and($contentBlock->getFieldValue('cbMatrix')->one()->getFieldValue('blockText'))->toBe('one');
});

it('still honours a fields wrapper when one is given', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        'fields' => ['cbText' => 'foo'],
    ]));

    expect(EntryElement::find()->title('imported entry')->one()->getFieldValue('myContentBlock')->getFieldValue('cbText'))
        ->toBe('foo');
});

// A content block only comes into existence when its owner saves, so importing one into an element
// that's already saved and has no block yet depends on change detection noticing it.
it('creates a content block on an element that has none yet', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    // the seeded entry exists already and has no content block
    $this->import->importItem($importer, [
        'title' => 'seed entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'matchCriteria' => ['title' => 'title'],
        'myContentBlock' => [
            'fields' => [
                'cbText' => 'foo',
                'cbMatrix' => [
                    ['type' => 'cbBlockEt', 'title' => 'cb block 1', 'fields' => ['blockText' => 'one']],
                ],
            ],
        ],
    ]);

    $contentBlock = EntryElement::find()->title('seed entry')->status(null)->one()->getFieldValue('myContentBlock');

    expect($contentBlock?->id)->not->toBeNull()
        ->and($contentBlock->getFieldValue('cbText'))->toBe('foo')
        ->and($contentBlock->getFieldValue('cbMatrix')->one()?->getFieldValue('blockText'))->toBe('one');
});
