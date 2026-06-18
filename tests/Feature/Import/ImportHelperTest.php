<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Fields as FieldsService;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\ImportHelper;
use CraftCms\Cms\Support\Str;

it('returns an empty array when the field layout is null', function () {
    $result = ImportHelper::getDestinationColsForFieldLayout(null);

    expect($result)->toBe([]);
});

it('marks non-container fields as not a container', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            ['uid' => Str::uuid()->toString(), 'type' => CustomField::class, 'fieldUid' => $plainTextField->uid, 'required' => false],
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
            ['uid' => Str::uuid()->toString(), 'type' => CustomField::class, 'fieldUid' => $matrixFieldModel->uid, 'required' => false],
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'myMatrix');

    expect($col)->not()->toBeNull();
    expect($col['isContainer'])->toBeTrue();
    expect($col['fieldUid'])->toBe($matrixFieldModel->uid);
});

it('uses map[attr] as the prefixedHandle for top-level fields without an owner field', function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);
    Fields::refreshFields();

    $fieldLayoutModel = FieldLayout::factory()
        ->withContentTab([
            ['uid' => Str::uuid()->toString(), 'type' => CustomField::class, 'fieldUid' => $plainTextField->uid, 'required' => false],
        ])
        ->create();

    $fieldLayout = app(FieldsService::class)->getLayoutByUid($fieldLayoutModel->uid);
    $cols = ImportHelper::getDestinationColsForFieldLayout($fieldLayout);
    $col = collect($cols)->firstWhere('handle', 'plainText');

    expect($col['prefixedHandle'])->toBe('map[plainText]');
    expect($col['prefixedHandleWithoutMap'])->toBe('plainText');
});
