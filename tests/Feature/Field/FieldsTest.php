<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Events\CompatibleFieldTypesResolving;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\NestedEntryFieldTypes;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField as CustomFieldElement;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->fields = app(Fields::class);
});

it('is a singleton', function () {
    expect($this->fields)->toBe(app(Fields::class));
    expect($this->fields)->toBe(FieldsFacade::getFacadeRoot());
});

it('can get and set context', function () {
    expect($this->fields->getFieldContext())->toBe('global');
    expect($this->fields->fieldContext)->toBe('global');

    $this->fields->setFieldContext('foo');

    expect($this->fields->getFieldContext())->toBe('foo');
    expect($this->fields->fieldContext)->toBe('foo');
});

it('can get all field types that have content', function () {
    class CustomFieldWithoutContent extends Field
    {
        #[Override]
        public static function dbType(): null
        {
            return null;
        }
    }

    app(FieldTypes::class)->register(CustomFieldWithoutContent::class);

    expect($this->fields->getFieldTypesWithContent())->toContain(PlainText::class);
    expect($this->fields->getFieldTypesWithContent())->not()->toContain(CustomFieldWithoutContent::class);
});

it('can get compatible field types for a given field', function () {
    $plainText = new PlainText;

    expect($this->fields->getCompatibleFieldTypes($plainText))->toContain(PlainText::class);
    expect($this->fields->getCompatibleFieldTypes($plainText))->toContain(Color::class);
    expect($this->fields->getCompatibleFieldTypes($plainText))->not()->toContain(Matrix::class);
    expect($this->fields->getCompatibleFieldTypes($plainText, includeCurrent: false))->not()->toContain(PlainText::class);
});

it('can define additional compatible field types with an event', function () {
    $plainText = new PlainText;

    class CustomCompatibleField extends Field {}

    expect($this->fields->getCompatibleFieldTypes($plainText))->not()->toContain(CustomCompatibleField::class);

    Event::listen(
        CompatibleFieldTypesResolving::class,
        fn (CompatibleFieldTypesResolving $event) => $event->compatibleTypes->add(CustomCompatibleField::class),
    );

    expect($this->fields->getCompatibleFieldTypes($plainText))->toContain(CustomCompatibleField::class);
});

it('can determine if two field types are compatible', function () {
    expect($this->fields->areFieldTypesCompatible(PlainText::class, Color::class))->toBeTrue();
    expect($this->fields->areFieldTypesCompatible(PlainText::class, Matrix::class))->toBeFalse();
});

it('can get nested entry field types', function () {
    class CustomNestedEntryField extends Matrix {}

    expect($this->fields->getNestedEntryFieldTypes())->toContain(Matrix::class);
    expect($this->fields->getNestedEntryFieldTypes())->not()->toContain(CustomNestedEntryField::class);

    app(NestedEntryFieldTypes::class)->register(CustomNestedEntryField::class);

    expect($this->fields->getNestedEntryFieldTypes())->toContain(CustomNestedEntryField::class);
});

it('can get relational field types', function () {
    expect($this->fields->getRelationalFieldTypes())->not()->toContain(PlainText::class);
    expect($this->fields->getRelationalFieldTypes())->toContain(Entries::class);
});

it('can create a field with a config', function () {
    expect($this->fields->createField([
        'type' => PlainText::class,
    ]))->toBeInstanceOf(PlainText::class);
});

it('normalizes translation methods to enums for src fields', function () {
    $field = $this->fields->createField([
        'type' => PlainText::class,
        'translationMethod' => 'site',
    ]);

    expect($field->translationMethod)->toBe('site')
        ->and($field->translationMethodValue)->toBe('site')
        ->and($field->getTranslationDescription(null))->toBe(TranslationMethod::Site->description());
});

it('falls back to the first supported translation method for invalid src field values', function () {
    $field = $this->fields->createField([
        'type' => PlainText::class,
        'translationMethod' => 'invalid',
    ]);

    expect($field->translationMethod)->toBe(TranslationMethod::None->value)
        ->and($field->translationMethodValue)->toBe(TranslationMethod::None->value)
        ->and($field->getTranslationDescription(null))->toBeNull();
});

it('creates a missing field if the field isnt recognized', function () {
    $field = $this->fields->createField([
        'type' => 'some\\unrecognized\\Field',
    ]);

    expect($field)->toBeInstanceOf(MissingField::class);
    expect($field->expectedType)->toBe('some\\unrecognized\\Field');
});

it('can get all fields', function () {
    expect($this->fields->getAllFields())->toBeEmpty();

    $this->fields->saveField($this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ]));

    expect($this->fields->getAllFields())->not()->toBeEmpty();
});

it('can get all fields with or without content', function () {
    $this->fields->saveField($this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ]));

    expect($this->fields->getFieldsWithContent())->not()->toBeEmpty();
    expect($this->fields->getFieldsWithoutContent())->toBeEmpty();
});

it('can get fields by type', function () {
    $this->fields->saveField($this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ]));

    expect($this->fields->getFieldsByType(PlainText::class))->toHaveCount(1);
    expect($this->fields->getFieldsByType(Matrix::class))->toBeEmpty();
});

it('can get a field by id, uid and handle', function () {
    $this->fields->saveField($field = $this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ]));

    expect($this->fields->getFieldById($field->id))->toBeInstanceOf(PlainText::class);
    expect($this->fields->getFieldByUid($field->uid))->toBeInstanceOf(PlainText::class);
    expect($this->fields->getFieldByHandle('plainText'))->toBeInstanceOf(PlainText::class);
    expect($this->fields->doesFieldWithHandleExist('plainText'))->toBeTrue();
});

it('can create a field config from a field', function () {
    $field = $this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ]);

    expect($this->fields->createFieldConfig($field))->toBeArray();
    expect($this->fields->createFieldConfig($field))->toBe([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'instructions' => null,
        'searchable' => false,
        'translationMethod' => 'none',
        'translationKeyFormat' => null,
        'type' => PlainText::class,
        'settings' => [
            'uiMode' => 'normal',
            'placeholder' => null,
            'code' => false,
            'multiline' => false,
            'initialRows' => 4,
            'charLimit' => null,
            'byteLimit' => null,
        ],
    ]);
});

it('can save a field', function () {
    expect(FieldModel::count())->toBe(0);

    expect($this->fields->saveField($this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ])))->toBeTrue();

    expect(FieldModel::count())->toBe(1);
});

it('preps a field for saving', function () {
    $field = $this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ]);

    expect($field->uid)->toBeNull();

    $this->fields->prepFieldForSave($field);

    expect($field->uid)->not()->toBeNull();
});

it('can delete a field by id', function () {
    expect(FieldModel::count())->toBe(0);

    expect($this->fields->saveField($field = $this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ])))->toBeTrue();

    expect(FieldModel::count())->toBe(1);

    $this->fields->deleteFieldById($field->id);

    expect(FieldModel::count())->toBe(0);
});

it('can delete a field', function () {
    expect(FieldModel::count())->toBe(0);

    expect($this->fields->saveField($field = $this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Plain Text',
        'handle' => 'plainText',
    ])))->toBeTrue();

    expect(FieldModel::count())->toBe(1);

    $this->fields->deleteField($field);

    expect(FieldModel::count())->toBe(0);
});

it('can hard delete a field layout by id', function () {
    $field = FieldModel::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainTextLayoutField',
        'type' => PlainText::class,
    ]);
    FieldsFacade::refreshFields();

    $layout = FieldLayout::make(EntryElement::class)
        ->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomFieldElement::make($field->handle)));

    expect($this->fields->saveLayout($layout))->toBeTrue();

    expect($this->fields->getLayoutById($layout->id))->not()->toBeNull();

    expect($this->fields->deleteLayoutById($layout->id, hardDelete: true))->toBeTrue();

    expect(FieldLayoutModel::withTrashed()->find($layout->id))->toBeNull()
        ->and($this->fields->getLayoutById($layout->id))->toBeNull();
});

it('rejects saving a content block field whose nested layout references itself', function () {
    $fieldUid = (string) Str::uuid();

    $field = $this->fields->createField([
        'type' => ContentBlock::class,
        'name' => 'Recursive Block',
        'handle' => 'recursiveBlock',
        'uid' => $fieldUid,
        'settings' => [
            'viewMode' => 'grouped',
            'fieldLayouts' => [
                (string) Str::uuid() => [
                    'tabs' => [[
                        'uid' => (string) Str::uuid(),
                        'elements' => [[
                            'type' => CustomFieldElement::class,
                            'uid' => (string) Str::uuid(),
                            'width' => 100,
                            'required' => false,
                            'fieldUid' => $fieldUid,
                        ]],
                    ]],
                ],
            ],
        ],
    ]);

    expect($this->fields->saveField($field))->toBeFalse()
        ->and($field->errors()->first('fieldLayout'))->toBe('Including a Content Block field recursively is not allowed.')
        ->and(FieldModel::count())->toBe(0);
});

it('rejects re-saving a content block field with its own layout element added', function () {
    $field = $this->fields->createField([
        'type' => ContentBlock::class,
        'name' => 'Recursive Block',
        'handle' => 'recursiveBlock',
    ]);

    expect($this->fields->saveField($field))->toBeTrue();

    $field->setFieldLayouts([
        (string) Str::uuid() => [
            'tabs' => [[
                'uid' => (string) Str::uuid(),
                'elements' => [[
                    'type' => CustomFieldElement::class,
                    'uid' => (string) Str::uuid(),
                    'fieldUid' => $field->uid,
                ]],
            ]],
        ],
    ]);

    expect($this->fields->saveField($field))->toBeFalse()
        ->and($field->errors()->first('fieldLayout'))->toBe('Including a Content Block field recursively is not allowed.');
});

it('allows nesting a different content block field', function () {
    $innerField = $this->fields->createField([
        'type' => ContentBlock::class,
        'name' => 'Inner Block',
        'handle' => 'innerBlock',
    ]);

    expect($this->fields->saveField($innerField))->toBeTrue();

    $outerField = $this->fields->createField([
        'type' => ContentBlock::class,
        'name' => 'Outer Block',
        'handle' => 'outerBlock',
        'settings' => [
            'fieldLayouts' => [
                (string) Str::uuid() => [
                    'tabs' => [[
                        'uid' => (string) Str::uuid(),
                        'elements' => [[
                            'type' => CustomFieldElement::class,
                            'uid' => (string) Str::uuid(),
                            'fieldUid' => $innerField->uid,
                        ]],
                    ]],
                ],
            ],
        ],
    ]);

    expect($this->fields->saveField($outerField))->toBeTrue()
        ->and(FieldModel::count())->toBe(2);
});

it('can find field usages', function () {
    expect($this->fields->findFieldUsages(new PlainText))->toBeEmpty();

    $this->markTestIncomplete('Add test with field usage');
});

it('can merge fields', function () {
    $this->fields->saveField($this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Persisting Text',
        'handle' => 'persistingText',
    ]));

    $this->fields->saveField($this->fields->createField([
        'type' => PlainText::class,
        'name' => 'Outgoing Text',
        'handle' => 'outgoingText',
    ]));

    $persistingField = $this->fields->getFieldByHandle('persistingText');
    $outgoingField = $this->fields->getFieldByHandle('outgoingText');

    $layoutModel = FieldLayoutModel::factory()
        ->forField(FieldModel::firstWhere('handle', 'outgoingText'))
        ->create();

    EntryType::factory()->create([
        'fieldLayoutId' => $layoutModel->id,
    ]);

    $migrationPath = null;

    try {
        $result = $this->fields->merge($persistingField, $outgoingField);
        $migrationPath = $result->migrationPath;

        $layout = $this->fields->getLayoutById($layoutModel->id);
        $layoutElement = $layout->getCustomFieldElements()[0];

        expect($result->updatedLayouts)->toBe(1)
            ->and($this->fields->getFieldByHandle('outgoingText'))->toBeNull()
            ->and($layoutElement->getFieldUid())->toBe($persistingField->uid)
            ->and($migrationPath)->toBeFile()
            ->and(DB::table(Table::MIGRATIONS)->where('migration', basename((string) $migrationPath, '.php'))->exists())->toBeTrue();
    } finally {
        if ($migrationPath) {
            @unlink($migrationPath);
        }
    }
});

it('returns laravel-style pagination metadata for table data', function () {
    foreach (range(1, 3) as $index) {
        $this->fields->saveField($this->fields->createField([
            'type' => PlainText::class,
            'name' => "Plain Text {$index}",
            'handle' => "plainText{$index}",
        ]));
    }

    [$pagination] = $this->fields->getTableData(2, 2, null, 'name', SORT_ASC);

    expect($pagination)
        ->toMatchArray([
            'total' => 3,
            'per_page' => 2,
            'current_page' => 2,
            'last_page' => 2,
            'from' => 3,
            'to' => 3,
        ])
        ->and($pagination['prev_page_url'])->toContain('page=1')
        ->and($pagination['next_page_url'])->toBeNull();
});

test('field layouts', function () {
    $field = FieldModel::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainTextLayoutField',
        'type' => PlainText::class,
    ]);
    FieldsFacade::refreshFields();

    $layout = FieldLayout::make(EntryElement::class)
        ->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomFieldElement::make($field->handle)));

    expect($this->fields->saveLayout($layout))->toBeTrue()
        ->and($layout->id)->toBeInt()
        ->and($this->fields->getLayoutById($layout->id)?->uid)->toBe($layout->uid)
        ->and($this->fields->getLayoutByUid($layout->uid)?->id)->toBe($layout->id)
        ->and($this->fields->getLayoutByType(EntryElement::class, false)?->id)->toBe($layout->id);

    $layout
        ->removeTab(FieldLayout::defaultTabName())
        ->tab('Updated', fn (FieldLayoutTab $tab) => $tab->add(CustomFieldElement::make($field->handle)));

    expect($this->fields->saveLayout($layout))->toBeTrue();

    $savedLayout = $this->fields->getLayoutById($layout->id);

    expect($savedLayout?->getTabs())->toHaveCount(1)
        ->and($savedLayout?->getTab('Updated'))->toBeInstanceOf(FieldLayoutTab::class);
});
