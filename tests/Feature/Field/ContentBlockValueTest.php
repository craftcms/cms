<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;

it('loads the content block for a clone of an entry from a multi-result query', function () {
    $innerField = Field::factory()->create(['handle' => 'blockText', 'type' => PlainText::class]);
    Fields::refreshFields();
    $contentBlockField = Field::factory()->create([
        'handle' => 'testBlock',
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
    Fields::refreshFields();
    $entryType = EntryType::factory()->withField($contentBlockField)->create();
    $section = Section::factory()->withEntryTypes($entryType)->create(['type' => SectionType::Channel]);

    $entryIds = [];
    foreach (['first', 'second'] as $text) {
        $entry = Entry::factory()->forSection($section)->forEntryType($entryType)->createElement();
        $entry->setFieldValue('testBlock', ['fields' => ['blockText' => $text]]);
        Elements::saveElement($entry);
        $entryIds[] = $entry->id;
    }

    [$entry] = EntryElement::find()->id($entryIds)->fixedOrder()->status(null)->all();
    $clone = clone $entry;

    $contentBlock = $clone->getFieldValue('testBlock');

    expect($contentBlock->id)->not->toBeNull()
        ->and($contentBlock->getFieldValue('blockText'))->toBe('first');
});
