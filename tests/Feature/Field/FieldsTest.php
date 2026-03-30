<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Events\DefineCompatibleFieldTypes;
use CraftCms\Cms\Field\Events\RegisterFieldTypes;
use CraftCms\Cms\Field\Events\RegisterNestedEntryFieldTypes;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\MissingField;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
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

it('can get all field types', function () {
    expect($this->fields->getAllFieldTypes())->not()->toBeEmpty();

    foreach ($this->fields->getAllFieldTypes() as $type) {
        expect($type)->toExtend(Field::class);
    }
});

it('can add extra field types through an event', function () {
    class CustomField extends Field {}

    Event::listen(
        RegisterFieldTypes::class,
        fn (RegisterFieldTypes $event) => $event->types->add(CustomField::class),
    );

    expect($this->fields->getAllFieldTypes())->toContain(CustomField::class);
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

    Event::listen(
        RegisterFieldTypes::class,
        fn (RegisterFieldTypes $event) => $event->types->add(CustomFieldWithoutContent::class),
    );

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
        DefineCompatibleFieldTypes::class,
        fn (DefineCompatibleFieldTypes $event) => $event->compatibleTypes->add(CustomCompatibleField::class),
    );

    expect($this->fields->getCompatibleFieldTypes($plainText))->toContain(CustomCompatibleField::class);
});

it('can determine if two field types are compatible', function () {
    expect($this->fields->areFieldTypesCompatible(PlainText::class, Color::class))->toBeTrue();
    expect($this->fields->areFieldTypesCompatible(PlainText::class, Matrix::class))->toBeFalse();
});

it('can get nested entry field types', function () {
    class CustomNestedEntryField extends Field {}

    expect($this->fields->getNestedEntryFieldTypes())->toContain(Matrix::class);
    expect($this->fields->getNestedEntryFieldTypes())->not()->toContain(CustomNestedEntryField::class);

    Event::listen(
        RegisterNestedEntryFieldTypes::class,
        fn (RegisterNestedEntryFieldTypes $event) => $event->types->add(CustomNestedEntryField::class),
    );

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

it('can get all fields with content', function () {
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

it('can find field usages', function () {
    expect($this->fields->findFieldUsages(new PlainText))->toBeEmpty();

    $this->markTestIncomplete('Add test with field usage');
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

test('field layouts')->todo('Implement once field layouts are ported.');
