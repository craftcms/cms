<?php

declare(strict_types=1);

use craft\behaviors\CustomFieldBehavior;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\FieldElementRulesHelper;

test('content block field merges nested field errors onto the element', function () {
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    CustomFieldBehavior::$fieldHandles[$innerField->handle] = true;
    Fields::refreshFields();

    $layoutUid = Str::uuid()->toString();
    $contentBlockSettings = [
        'fieldLayouts' => [
            $layoutUid => FieldElementRulesHelper::fieldLayoutConfig($innerField, true),
        ],
    ];

    [$entry] = FieldElementRulesHelper::createEntryWithField(
        handle: 'contentBlock',
        fieldType: ContentBlock::class,
        fieldSettings: $contentBlockSettings,
        value: ['fields' => ['innerText' => null]],
        scenario: Element::SCENARIO_LIVE,
    );

    $entry->validate();

    expect($entry->errors()->has('contentBlock.innerText'))->toBeTrue();
});
