<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\FieldElementRulesHelper;

function createMatrixEntryType(): EntryTypeModel
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
    $entryType = createMatrixEntryType();

    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'matrixField',
        fieldType: Matrix::class,
        fieldSettings: ['entryTypes' => [$entryType->id], 'minEntries' => 1],
        value: '',
        scenario: Element::SCENARIO_LIVE,
    );

    $entry->validate();

    expect($entry->errors()->has('matrixField'))->toBeTrue();
});

test('matrix field surfaces nested entry validation errors', function () {
    $entryType = createMatrixEntryType();

    $value = [
        'new1' => [
            'type' => $entryType->handle,
            'title' => '',
            'enabled' => true,
            'fields' => [],
        ],
    ];

    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'matrixField',
        fieldType: Matrix::class,
        fieldSettings: ['entryTypes' => [$entryType->id], 'viewMode' => Matrix::VIEW_MODE_INDEX],
        value: $value,
        scenario: Element::SCENARIO_LIVE,
    );

    $entry->validate();

    expect($entry->errors()->has('matrixField'))->toBeTrue();
});
