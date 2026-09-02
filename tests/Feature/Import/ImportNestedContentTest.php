<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Addresses as AddressesField;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;

beforeEach(function () {
    $this->import = app(Import::class);

    // Fields inside the content block
    $cbTextField = Field::factory()->create([
        'name' => 'CB Text',
        'handle' => 'cbText',
        'type' => PlainText::class,
    ]);

    $cbAddressesField = Field::factory()->create([
        'name' => 'CB Addresses',
        'handle' => 'cbAddresses',
        'type' => AddressesField::class,
    ]);

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
                        'elements' => [
                            [
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $cbTextField->uid,
                                'required' => false,
                            ],
                            [
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $cbAddressesField->uid,
                                'required' => false,
                            ],
                        ],
                    ]],
                ],
            ],
        ],
    ]);

    Fields::refreshFields();

    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);

    Fields::refreshFields();

    $forMatrixEntryType = EntryType::factory()
        ->withFieldLayout(
            FieldLayout::factory()
                ->withContentTab([
                    new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                    CustomField::make($plainTextField->handle),
                    CustomField::make($contentBlockField->handle),
                ])
                ->create()
        )
        ->create([
            'name' => 'For Matrix Entry Type',
            'handle' => 'forMatrixEntryType',
            'hasTitleField' => true,
        ]);

    $matrixField = Field::factory()->create([
        'name' => 'My Matrix',
        'handle' => 'myMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$forMatrixEntryType->id]],
    ]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $fieldLayout = FieldLayout::factory()
        ->withContentTab([
            new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
            CustomField::make($matrixField->handle),
        ])
        ->create();

    $entryType = EntryType::factory()
        ->withFieldLayout($fieldLayout)
        ->create([
            'name' => 'With Matrix Field',
            'handle' => 'withMatrixField',
            'hasTitleField' => true,
        ]);

    $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

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

    $this->address = [
        'title' => 'address 1',
        'countryCode' => 'US',
        'addressLine1' => '123 Main St',
        'administrativeArea' => 'UT',
        'postalCode' => '12345',
        'locality' => 'My Town',
    ];

    $this->entryData = fn (array $blocks) => [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myMatrix' => $blocks,
    ];
});

it('imports all fields through the matrix → content block → plain text and addresses chain', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        [
            'type' => 'forMatrixEntryType',
            'title' => 'block 1',
            'fields' => [
                'plainText' => 'block text value',
                'myContentBlock' => [
                    'fields' => [
                        'cbText' => 'content block text',
                        'cbAddresses' => [$this->address],
                    ],
                ],
            ],
        ],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect($entry)->not()->toBeNull();

    $block = $entry->getFieldValue('myMatrix')->one();
    expect($block->getFieldValue('plainText'))->toBe('block text value');

    $contentBlock = $block->getFieldValue('myContentBlock');
    expect($contentBlock)->not()->toBeNull();
    expect($contentBlock->getFieldValue('cbText'))->toBe('content block text');

    $address = Address::find()->ownerId($contentBlock->id)->one();
    expect($address)->not()->toBeNull()
        ->and($address->countryCode)->toBe('US')
        ->and($address->addressLine1)->toBe('123 Main St');
});

it('updates values at all levels on reimport', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $block = [
        'type' => 'forMatrixEntryType',
        'title' => 'block 1',
        'matchCriteria' => ['title' => 'title'],
        'fields' => [
            'plainText' => 'block text value',
            'myContentBlock' => [
                'fields' => [
                    'cbText' => 'content block text',
                    'cbAddresses' => [$this->address],
                ],
            ],
        ],
    ];

    $this->import->importItem($importer, ($this->entryData)([$block]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $contentBlock = $entry->getFieldValue('myMatrix')->one()->getFieldValue('myContentBlock');
    expect($contentBlock->getFieldValue('cbText'))->toBe('content block text');
    expect(Address::find()->ownerId($contentBlock->id)->count())->toBe(1);

    $block['fields']['plainText'] = 'updated block text';
    $block['fields']['myContentBlock']['fields']['cbText'] = 'updated content block text';
    $block['fields']['myContentBlock']['fields']['cbAddresses'][0]['addressLine1'] = '456 Updated Ave';

    $this->import->importItem($importer, ($this->entryData)([$block]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $matrixBlock = $entry->getFieldValue('myMatrix')->one();
    expect($matrixBlock->getFieldValue('plainText'))->toBe('updated block text');

    $contentBlock = $matrixBlock->getFieldValue('myContentBlock');
    expect($contentBlock->getFieldValue('cbText'))->toBe('updated content block text');
    expect(Address::find()->ownerId($contentBlock->id)->one()->addressLine1)->toBe('456 Updated Ave');
});
