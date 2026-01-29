<?php

declare(strict_types=1);

use craft\behaviors\CustomFieldBehavior;
use craft\fieldlayoutelements\CustomField;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;

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
        ->createElementWithFields();

    $result->element->validate();

    expect($result->element->errors()->has('contentBlock.innerText'))->toBeTrue();
});
