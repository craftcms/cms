<?php

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

it('can query custom fields', function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'textField',
        'type' => PlainText::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $entryModel = EntryModel::factory()->create();
    $entryModel->element->update([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    $entryModel->entryType->update([
        'fieldLayoutId' => $fieldLayout->id,
    ]);

    app(Fields::class)->invalidateCaches();

    app(Fields::class)->refreshFields();

    /** @var Entry $entry */
    $entry = entryQuery()->first();
    $entry->title = 'Test entry';
    $entry->setFieldValue('textField', 'Foo');

    Elements::saveElement($entry);

    expect(entryQuery()->textField('Foo')->count())->toBe(1);
    expect(entryQuery()->textField('Fo*')->count())->toBe(1);
    expect(entryQuery()->textField([
        'value' => 'fo*',
        'caseInsensitive' => true,
    ])->count())->toBe(1);

    // SQLite's LIKE operator is case-insensitive for ASCII by default and does not
    // support case-sensitive wildcard matching without custom functions or GLOB.
    if (DB::getDriverName() !== 'sqlite') {
        expect(entryQuery()->textField([
            'value' => 'fo*',
            'caseInsensitive' => false,
        ])->count())->toBe(0);
    }

    expect(entryQuery()->textField('bar')->count())->toBe(0);
});
