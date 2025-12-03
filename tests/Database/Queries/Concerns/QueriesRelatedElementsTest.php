<?php

use craft\behaviors\CustomFieldBehavior;
use craft\fieldlayoutelements\CustomField;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;

test('related elements', function () {
    $field = Field::factory()->create([
        'handle' => 'entriesField',
        'type' => Entries::class,
    ]);

    $fieldLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => [
            'tabs' => [
                [
                    'uid' => \Illuminate\Support\Str::uuid()->toString(),
                    'name' => 'Tab 1',
                    'elements' => [
                        [
                            'uid' => \Illuminate\Support\Str::uuid()->toString(),
                            'type' => CustomField::class,
                            'fieldUid' => $field->uid,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $entries = EntryModel::factory(3)->create();
    foreach ($entries as $entryModel) {
        $entryModel->element->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        $entryModel->entryType->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);
    }

    CustomFieldBehavior::$fieldHandles[$field->handle] = true;

    Fields::refreshFields();

    $entry = entryQuery()->firstOrFail();
    $entry->title = 'Test entry';
    $entry->setFieldValue('entriesField', $entries[1]->id);

    Craft::$app->getElements()->saveElement($entry);

    expect(entryQuery()->count())->toBe(3);
    expect(entryQuery()->relatedTo($entries[1]->id)->count())->toBe(1);
    expect(entryQuery()->notRelatedTo($entries[1]->id)->count())->toBe(2);
});
