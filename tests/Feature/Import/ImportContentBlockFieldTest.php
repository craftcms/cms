<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;

beforeEach(function () {
    $this->import = app(Import::class);

    $innerField = Field::factory()->create([
        'name' => 'CB Text',
        'handle' => 'cbText',
        'type' => PlainText::class,
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
                        'elements' => [[
                            'uid' => Str::uuid()->toString(),
                            'type' => CustomField::class,
                            'fieldUid' => $innerField->uid,
                            'required' => false,
                        ]],
                    ]],
                ],
            ],
        ],
    ]);

    Fields::refreshFields();

    $fieldLayout = FieldLayout::factory()
        ->withContentTab([
            new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
            CustomField::make($contentBlockField->handle),
        ])
        ->create();

    $entryType = EntryType::factory()
        ->withFieldLayout($fieldLayout)
        ->create([
            'name' => 'With Content Block Field',
            'handle' => 'withContentBlockField',
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

    $this->entryData = fn (?array $contentBlock) => [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myContentBlock' => $contentBlock,
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
