<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;

beforeEach(function () {
    $this->field = FieldModel::factory()->create([
        'name' => 'Body',
        'handle' => 'body',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();
});

it('resolves custom fields by handle', function () {
    $element = CustomField::make('body');

    expect($element->getFieldUid())->toBe($this->field->uid)
        ->and($element->attribute())->toBe('body');
});

it('adds custom fields by handle', function () {
    $layout = new FieldLayout;
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab
        ->field('body', fn (CustomField $field) => $field->required()));

    $element = $layout->getTab(FieldLayout::defaultTabName())->getElements()[0];

    expect($element)->toBeInstanceOf(CustomField::class)
        ->and($element->getFieldUid())->toBe($this->field->uid)
        ->and($element->required)->toBeTrue();
});

it('rejects unknown field handles', function () {
    expect(fn () => CustomField::make('missing'))
        ->toThrow(InvalidArgumentException::class);
});

it('removes fields by handle and rejects unknown handles', function () {
    $layout = new FieldLayout;
    $layout->tab(FieldLayout::defaultTabName(), fn (FieldLayoutTab $tab) => $tab->add(CustomField::make('body')));

    expect($layout->removeField('body'))->toBe($layout)
        ->and($layout->getCustomFields())->toBeEmpty()
        ->and(fn () => $layout->removeField('missing'))
        ->toThrow(InvalidArgumentException::class);
});

it('modifies and persists a factory-created field layout', function () {
    $layoutModel = FieldLayoutModel::factory()->withContentTab()->create();
    $entryTypeModel = EntryType::factory()->withFieldLayout($layoutModel)->create();
    EntryTypes::refreshEntryTypes();

    $layout = EntryTypes::getEntryTypeById($entryTypeModel->id)->getFieldLayout();
    $layout->tab('SEO', fn (FieldLayoutTab $tab) => $tab->add(CustomField::make('body')->required()));

    expect(Fields::saveLayout($layout))->toBeTrue();

    $savedLayout = Fields::getLayoutById($layout->id);
    $savedField = array_find(
        $savedLayout->getTab('SEO')->getElements(),
        fn ($element) => $element instanceof CustomField && $element->getFieldUid() === $this->field->uid,
    );

    expect($savedField)->toBeInstanceOf(CustomField::class)
        ->and($savedField?->required)->toBeTrue();
});
