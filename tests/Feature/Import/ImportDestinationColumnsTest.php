<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Fields as FieldsService;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\ImportHelper;

it('marks non-container fields as not a container', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');

    expect($col)->not()->toBeNull();
    expect($col['isContainer'])->toBeFalse();
    expect($col)->not()->toHaveKey('fieldUid');
});

it('marks ImportableElementContainerFieldInterface fields as containers with a fieldUid', function () {
    $entryType = EntryType::factory()->create(['name' => 'Block', 'handle' => 'block']);

    $matrixFieldModel = Field::factory()->create([
        'name' => 'My Matrix',
        'handle' => 'myMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$entryType->id]],
    ]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($matrixFieldModel->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'myMatrix');

    expect($col)->not()->toBeNull();
    expect($col['isContainer'])->toBeTrue();
    expect($col['fieldUid'])->toBe($matrixFieldModel->uid);

    // a container holds nested elements rather than a value of its own, so neither
    // option applies to it — each of its nested columns carries its own decision
    expect($col['canBeMatchCriteria'])->toBeFalse();
    expect($col['canBeCleared'])->toBeFalse();
});

it('offers match criteria and clearing on an ordinary field', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');

    expect($col['canBeMatchCriteria'])->toBeTrue();
    expect($col['canBeCleared'])->toBeTrue();
});

// a relation field holds a value of its own (a list of ids), so unlike a container field it can be
// both matched on and cleared
it('offers match criteria and clearing on a relation field', function () {
    $entriesField = Field::factory()->create([
        'name' => 'My Entries',
        'handle' => 'myEntries',
        'type' => EntriesField::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($entriesField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'myEntries');

    expect($col['isContainer'])->toBeFalse()
        ->and($col['canBeCleared'])->toBeTrue();
});

it('uses map[attr] as the prefixedHandleForMap for top-level fields without an owner field', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            CustomField::make($plainTextField->handle),
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');

    expect($col['prefixedHandleForMap'])->toBe('map[plainText]');
    expect($col['prefixedHandleForMatchCriteria'])->toBe('matchCriteria[plainText]');
    expect($col['prefixedHandle'])->toBe('plainText');
    expect($col['prefixedHandleAsArray'])->toBe(['plainText']);
});
