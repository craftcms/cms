<?php

use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

test('related elements', function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'entriesField',
        'type' => Entries::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $entries = EntryModel::factory(3)->create();
    foreach ($entries as $entryModel) {
        $entryModel->element->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        $entryModel->entryType->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);
    }

    Fields::invalidateCaches();

    Fields::refreshFields();

    $entry = entryQuery()->firstOrFail();
    $entry->title = 'Test entry';
    $entry->setFieldValue('entriesField', $entries[1]->id);

    Craft::$app->getElements()->saveElement($entry);

    expect(entryQuery()->count())->toBe(3);
    expect(entryQuery()->relatedTo($entries[1]->id)->count())->toBe(1);
    expect(entryQuery()->notRelatedTo($entries[1]->id)->count())->toBe(2);
});
