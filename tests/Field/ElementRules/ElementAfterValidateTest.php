<?php

declare(strict_types=1);

use craft\fieldlayoutelements\CustomField;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\TestClasses\TestEntryWithAfterValidate;

test('afterValidate merges field errors onto the element', function () {
    $field = Field::factory()->create([
        'name' => 'Text Field',
        'handle' => 'textField',
        'type' => Email::class,
    ]);

    Fields::invalidateCaches();
    Fields::refreshFields();

    $layout = Fields::createLayout([
        'type' => EntryElement::class,
        'tabs' => [
            [
                'uid' => Str::uuid()->toString(),
                'name' => 'Content',
                'elements' => [
                    [
                        'uid' => Str::uuid()->toString(),
                        'type' => CustomField::class,
                        'fieldUid' => $field->uid,
                        'required' => false,
                    ],
                ],
            ],
        ],
    ]);

    $entry = new TestEntryWithAfterValidate;
    $entry->title = 'Test entry';
    $entry->setScenario(Element::SCENARIO_DEFAULT);
    $entry->setMockFieldLayout($layout);
    $entry->setFieldValue($field->handle, 'not-an-email');

    $entry->validate();

    expect($entry->afterValidateCalled)->toBeTrue();
    expect($entry->errors()->has($field->handle))->toBeTrue();
    expect($entry->errors()->has('customError'))->toBeTrue();
});
