<?php

use craft\behaviors\CustomFieldBehavior;
use craft\fieldlayoutelements\CustomField;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

it('can query custom fields', function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'textField',
        'type' => PlainText::class,
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

    $entryModel = EntryModel::factory()->create();
    $entryModel->element->update([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    $entryModel->entryType->update([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    CustomFieldBehavior::$fieldHandles[$field->handle] = true;

    Fields::refreshFields();

    /** @var \CraftCms\Cms\Entry\Elements\Entry $entry */
    $entry = entryQuery()->first();
    $entry->title = 'Test entry';
    $entry->setFieldValue('textField', 'Foo');

    Craft::$app->getElements()->saveElement($entry);

    expect(entryQuery()->textField('Foo')->count())->toBe(1);
    expect(entryQuery()->textField('Fo*')->count())->toBe(1);
    expect(entryQuery()->textField([
        'value' => 'fo*',
        'caseInsensitive' => true,
    ])->count())->toBe(1);
    expect(entryQuery()->textField([
        'value' => 'fo*',
        'caseInsensitive' => false,
    ])->count())->toBe(0);
    expect(entryQuery()->textField('bar')->count())->toBe(0);
});
