<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Fields as FieldsService;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\ImportHelper;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\ImportFixtures;

it('marks non-container fields as not a container', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');

    expect($col)->not()->toBeNull();
    expect($col->isContainer)->toBeFalse();
    expect($col->fieldUid)->toBeNull();
});

it('marks ImportableElementContainerFieldInterface fields as containers with a fieldUid', function () {
    $entryType = EntryType::factory()->create(['name' => 'Block', 'handle' => 'block']);

    $matrixFieldModel = Field::factory()->create([
        'name' => 'My Matrix',
        'handle' => 'myMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$entryType->id]],
    ]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($matrixFieldModel->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'myMatrix');

    expect($col)->not()->toBeNull();
    expect($col->isContainer)->toBeTrue();
    expect($col->fieldUid)->toBe($matrixFieldModel->uid);

    expect($col->canBeMatchCriteria)->toBeFalse();
    expect($col->canBeCleared)->toBeFalse();
});

it('offers match criteria and clearing on an ordinary field', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');

    expect($col->canBeMatchCriteria)->toBeTrue();
    expect($col->canBeCleared)->toBeTrue();
});

it('offers match criteria and clearing on a relation field', function () {
    $entriesField = Field::factory()->create([
        'name' => 'My Entries',
        'handle' => 'myEntries',
        'type' => EntriesField::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($entriesField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'myEntries');

    expect($col->isContainer)->toBeFalse()
        ->and($col->canBeCleared)->toBeTrue();
});

it('names a top-level custom field by its layout element uid', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');
    $elementUid = $fieldLayout->getCustomFieldElements()[0]->uid;

    expect($col->prefixedHandleForMap)->toBe("map[$elementUid]");
    expect($col->prefixedHandleForMatchCriteria)->toBe("matchCriteria[$elementUid]");
    expect($col->prefixedHandle)->toBe($elementUid);
    expect($col->prefixedHandleAsArray)->toBe([$elementUid]);
});

// A content block is its own layout provider, so its handle appears once.
it('names the keep flag for a container field inside a content block without repeating the block handle', function () {
    $blockTextField = ImportFixtures::plainTextField('blockText', 'Block Text');
    $blockEntryType = ImportFixtures::blockEntryType('cbBlockEt', [$blockTextField], 'CB Block ET');
    $cbMatrixField = ImportFixtures::matrixField('cbMatrix', [$blockEntryType], 'CB Matrix');
    Fields::refreshFields();

    $elementUid = Str::uuid()->toString();

    Field::factory()->create([
        'name' => 'My Content Block',
        'handle' => 'myContentBlock',
        'type' => ContentBlockField::class,
        'settings' => ['fieldLayouts' => [Str::uuid()->toString() => ['tabs' => [[
            'uid' => Str::uuid()->toString(),
            'name' => 'Content',
            'elements' => [[
                'uid' => $elementUid,
                'type' => CustomField::class,
                'fieldUid' => $cbMatrixField->uid,
                'required' => false,
            ]],
        ]]]]],
    ]);
    Fields::refreshFields();

    $field = app(FieldsService::class)->getFieldByHandle('myContentBlock');

    $provider = $field->getFieldLayoutProviders()[0];
    $cols = ImportHelper::getDestinationColsForFieldLayout($provider->getFieldLayout(), $field, $provider, 'myContentBlock');
    $col = collect($cols)->firstWhere('handle', 'cbMatrix');

    expect($col->prefixedHandle)->toBe("myContentBlock[fields][$elementUid]")
        ->and($col->prefixedHandleForKeepFlag)->toBe("keepMissingNestedElements[myContentBlock][fields][$elementUid][__keep__]")
        ->and($col->prefixedHandleAsArray)->toBe(['myContentBlock', 'fields', $elementUid]);
});

it('enters a field’s default value with the input its type and settings call for', function () {
    $fields = [
        Field::factory()->create(['name' => 'Plain Text', 'handle' => 'plainText', 'type' => PlainText::class]),
        Field::factory()->create([
            'name' => 'Notes',
            'handle' => 'notes',
            'type' => PlainText::class,
            'settings' => ['multiline' => true, 'charLimit' => 200, 'placeholder' => 'Anything else?'],
        ]),
        Field::factory()->create([
            'name' => 'Featured',
            'handle' => 'featured',
            'type' => Lightswitch::class,
            'settings' => ['onLabel' => 'Yes', 'offLabel' => 'No'],
        ]),
        Field::factory()->create([
            'name' => 'Reading Time',
            'handle' => 'readingTime',
            'type' => Number::class,
            'settings' => ['min' => 1, 'max' => 60, 'decimals' => 1, 'suffix' => 'min'],
        ]),
        Field::factory()->create([
            'name' => 'Published',
            'handle' => 'published',
            'type' => Date::class,
            'settings' => ['showTime' => true],
        ]),
        Field::factory()->create([
            'name' => 'Difficulty',
            'handle' => 'difficulty',
            'type' => Dropdown::class,
            'settings' => ['options' => [
                ['label' => 'Beginner', 'value' => 'beginner', 'default' => true],
                ['label' => 'Advanced', 'value' => 'advanced', 'default' => false],
            ]],
        ]),
        Field::factory()->create([
            'name' => 'Topics',
            'handle' => 'topics',
            'type' => Checkboxes::class,
            'settings' => ['options' => [
                ['label' => 'News', 'value' => 'news', 'default' => false],
            ]],
        ]),
        Field::factory()->create(['name' => 'Related', 'handle' => 'related', 'type' => EntriesField::class]),
    ];
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab(array_map(fn ($field) => CustomField::make($field->handle), $fields))
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = collect(ImportHelper::getDestinationColsForFieldLayout($fieldLayout))->keyBy('handle');

    expect($cols['plainText']->defaultValueInput)->toBe(['type' => 'text'])
        ->and($cols['notes']->defaultValueInput)->toBe(['type' => 'textarea', 'placeholder' => 'Anything else?', 'maxLength' => 200])
        ->and($cols['featured']->defaultValueInput)->toBe(['type' => 'lightswitch', 'onLabel' => 'Yes', 'offLabel' => 'No'])
        ->and($cols['readingTime']->defaultValueInput)->toBe(['type' => 'number', 'min' => 1, 'max' => 60, 'step' => 0.1, 'suffix' => 'min'])
        ->and($cols['published']->defaultValueInput)->toBe(['type' => 'datetime'])
        ->and($cols['difficulty']->defaultValueInput)->toBe([
            'type' => 'select',
            'options' => [
                ['value' => 'beginner', 'label' => 'Beginner'],
                ['value' => 'advanced', 'label' => 'Advanced'],
            ],
        ])
        ->and($cols['topics']->defaultValueInput)->toBe([
            'type' => 'multiselect',
            'options' => [['value' => 'news', 'label' => 'News']],
        ])
        // no single value entered by hand suits a relation
        ->and($cols['related']->defaultValueInput)->toBeNull();
});

it('gives each destination column its field type’s icon', function () {
    $fields = [
        Field::factory()->create(['name' => 'Plain Text', 'handle' => 'plainText', 'type' => PlainText::class]),
        Field::factory()->create(['name' => 'Featured', 'handle' => 'featured', 'type' => Lightswitch::class]),
    ];
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab(array_map(fn ($field) => CustomField::make($field->handle), $fields))
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = collect(ImportHelper::getDestinationColsForFieldLayout($fieldLayout))->keyBy('handle');

    expect($cols['plainText']->icon)->toBe(PlainText::icon())
        ->and($cols['featured']->icon)->toBe(Lightswitch::icon());
});
