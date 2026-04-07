<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;

test('content block field merges nested field errors onto the element', function () {
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    Fields::invalidateCaches();
    Fields::refreshFields();

    $layoutUid = Str::uuid()->toString();
    $contentBlockSettings = [
        'fieldLayouts' => [
            $layoutUid => [
                'tabs' => [
                    [
                        'uid' => Str::uuid()->toString(),
                        'name' => 'Content',
                        'elements' => [
                            [
                                'uid' => Str::uuid()->toString(),
                                'type' => CustomField::class,
                                'fieldUid' => $innerField->uid,
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = EntryModel::factory()
        ->withField('contentBlock', ContentBlock::class, $contentBlockSettings, value: ['fields' => ['innerText' => null]])
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has('contentBlock.innerText'))->toBeTrue();
});

test('content block field preserves nested values through element validation', function () {
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $contentBlockField = Fields::createField([
        'name' => 'Content Block',
        'handle' => 'contentBlock',
        'type' => ContentBlock::class,
        'settings' => [
            'fieldLayouts' => [
                Str::uuid()->toString() => [
                    'tabs' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'name' => 'Content',
                            'elements' => [
                                [
                                    'uid' => Str::uuid()->toString(),
                                    'type' => CustomField::class,
                                    'fieldUid' => $innerField->uid,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect(Fields::saveField($contentBlockField))->toBeTrue();

    Fields::invalidateCaches();
    Fields::refreshFields();

    $result = EntryModel::factory()
        ->withField('contentBlock', ContentBlock::class, $contentBlockField->getSettings())
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields(save: false);

    $result->element->setFieldValueFromRequest('contentBlock', [
        'fields' => [
            'innerText' => 'Nested content block value',
        ],
    ]);

    expect($result->element->getFieldValue('contentBlock')->getFieldValue('innerText'))
        ->toBe('Nested content block value');

    $result->element->validate();

    expect($result->element->getFieldValue('contentBlock')->getFieldValue('innerText'))
        ->toBe('Nested content block value');
});

test('matrix field preserves nested values through element validation', function () {
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $entryTypeLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => [
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Content',
                    'elements' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'type' => CustomField::class,
                            'fieldUid' => $innerField->uid,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $matrixEntryType = EntryType::factory()->create([
        'fieldLayoutId' => $entryTypeLayout->id,
        'name' => 'Matrix Block',
        'handle' => 'matrixBlock',
        'hasTitleField' => true,
    ]);

    $matrixField = Field::factory()->create([
        'name' => 'Matrix Field',
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);

    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    $result = EntryModel::factory()
        ->withField('matrixField', Matrix::class, ['entryTypes' => [$matrixEntryType->id]])
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields(save: false);

    $blockUid = Str::uuid()->toString();

    $result->element->setFieldValueFromRequest('matrixField', [
        'entries' => [
            "uid:$blockUid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Block 1',
                'enabled' => true,
                'fields' => [
                    'innerText' => 'Nested matrix value',
                ],
            ],
        ],
        'sortOrder' => [$blockUid],
    ]);

    expect($result->element->getFieldValue('matrixField')->one()->getFieldValue('innerText'))
        ->toBe('Nested matrix value');

    $result->element->validate();

    expect($result->element->getFieldValue('matrixField')->one()->getFieldValue('innerText'))
        ->toBe('Nested matrix value');
});
