<?php

use craft\behaviors\CustomFieldBehavior;
use craft\fieldlayoutelements\CustomField;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'entriesField',
        'type' => Entries::class,
    ]);

    $fieldLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => [
            'tabs' => [
                [
                    'uid' => Str::uuid()->toString(),
                    'name' => 'Tab 1',
                    'elements' => [
                        [
                            'uid' => Str::uuid()->toString(),
                            'type' => CustomField::class,
                            'fieldUid' => $field->uid,
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $section = Section::factory()->create([
        'handle' => 'blog',
    ]);

    $entryType = EntryType::factory()->create([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    CustomFieldBehavior::$fieldHandles[$field->handle] = true;
    Fields::refreshFields();

    $entryModels = EntryModel::factory(10)->create([
        'sectionId' => $section->id,
        'typeId' => $entryType->id,
    ]);

    foreach ($entryModels as $model) {
        $model->element->update([
            'fieldLayoutId' => $fieldLayout->id,
        ]);

        $relatedEntry = EntryModel::factory()->create();

        $entryElement = entryQuery()->id($model->id)->firstOrFail();
        $entryElement->title = 'Test entry '.$model->id;
        $entryElement->setFieldValue('entriesField', [$relatedEntry->id]);
        Craft::$app->getElements()->saveElement($entryElement);
    }

    Craft::$app->getElements()->invalidateAllCaches();

    $this->entryModels = $entryModels;
});

test('with', function () {
    $result = entryQuery()->id($this->entryModels->first()->id)->first();

    expect($result->entriesField)->toBeInstanceOf(ElementQuery::class);

    $result = entryQuery()->id($this->entryModels->first()->id)->with('entriesField')->first();

    expect($result->entriesField)->toBeInstanceOf(ElementCollection::class);
});

test('eagerly', function () {
    $results = entryQuery()->section('blog')->get();

    $queryCountWithoutEagerly = 0;
    $queryCountWithEagerly = 0;

    \Illuminate\Support\Facades\DB::listen(function ($query) use (&$queryCountWithoutEagerly, &$queryCountWithEagerly) {
        $queryCountWithoutEagerly++;
        $queryCountWithEagerly++;
    });

    foreach ($results as $result) {
        $result->entriesField->first();
    }

    $queryCountWithoutEagerlyResults = $queryCountWithoutEagerly;

    $results = entryQuery()->section('blog')->get();

    $queryCountWithEagerly = 0;

    foreach ($results as $result) {
        $result->entriesField->eagerly()->first();
    }

    expect($queryCountWithEagerly)->toBeLessThan($queryCountWithoutEagerlyResults);
});
