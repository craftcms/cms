<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Support\Str;

function createMatrixRulesEntryType(): EntryTypeModel
{
    $layout = FieldLayoutModel::create([
        'type' => EntryElement::class,
        'config' => [
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Content',
                    'elements' => [],
                ],
            ],
        ],
    ]);

    return EntryTypeModel::factory()->create([
        'fieldLayoutId' => $layout->id,
        'name' => 'Block',
        'handle' => 'block',
        'hasTitleField' => true,
    ]);
}

test('matrix field enforces min entries', function () {
    $entryType = createMatrixRulesEntryType();

    $result = EntryModel::factory()
        ->withField('matrixField', Matrix::class, ['entryTypes' => [$entryType->id], 'minEntries' => 1], value: '')
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has('matrixField'))->toBeTrue();
});

test('matrix field surfaces nested entry validation errors', function () {
    $entryType = createMatrixRulesEntryType();

    $value = [
        'new1' => [
            'type' => $entryType->handle,
            'title' => '',
            'enabled' => true,
            'fields' => [],
        ],
    ];

    $result = EntryModel::factory()
        ->withField('matrixField', Matrix::class, ['entryTypes' => [$entryType->id], 'viewMode' => Matrix::VIEW_MODE_INDEX], value: $value)
        ->withScenario(Element::SCENARIO_LIVE)
        ->createElementWithFields(save: false);

    $result->element->validate();

    expect($result->element->errors()->has('matrixField'))->toBeTrue();
});
