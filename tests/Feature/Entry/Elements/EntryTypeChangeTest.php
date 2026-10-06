<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;

it('keeps the values of fields shared by the old and new entry types', function () {
    $field = Field::factory()->create(['handle' => 'summary', 'type' => PlainText::class]);
    $oldType = EntryType::factory()->withField($field)->create();
    $newType = EntryType::factory()->withField($field)->create();
    $section = Section::factory()->withEntryTypes($oldType, $newType)->create();
    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $entry = EntryModel::factory()->forSection($section)->forEntryType($oldType)->createElement();
    $entry->setFieldValue('summary', 'Hello');
    expect(Elements::saveElement($entry))->toBeTrue();

    $entry = EntryElement::find()->id($entry->id)->one();
    $entry->setAttributesFromRequest(['typeId' => $newType->id]);
    expect(Elements::saveElement($entry))->toBeTrue();

    $saved = EntryElement::find()->id($entry->id)->one();

    expect($saved->typeId)->toBe($newType->id)
        ->and($saved->getFieldValue('summary'))->toBe('Hello');
});
