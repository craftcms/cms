<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
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
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Tests\Support\ImportFixtures;

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

// The importer derives its keepMissingNestedElements lookups from these same handles
// (ElementImporter::collectAndEnableKeepFields()), so the shape is a contract between the two.
// A content block is its own layout provider, so its handle must appear once, not twice.
it('names the keep flag for a container field inside a content block without repeating the block handle', function () {
    $blockTextField = ImportFixtures::plainTextField('blockText', 'Block Text');
    $blockEntryType = ImportFixtures::blockEntryType('cbBlockEt', [$blockTextField], 'CB Block ET');
    $cbMatrixField = ImportFixtures::matrixField('cbMatrix', [$blockEntryType], 'CB Matrix');
    Fields::refreshFields();

    Field::factory()->create([
        'name' => 'My Content Block',
        'handle' => 'myContentBlock',
        'type' => ContentBlockField::class,
        'settings' => ['fieldLayouts' => [Str::uuid()->toString() => ['tabs' => [[
            'uid' => Str::uuid()->toString(),
            'name' => 'Content',
            'elements' => [[
                'uid' => Str::uuid()->toString(),
                'type' => CustomField::class,
                'fieldUid' => $cbMatrixField->uid,
                'required' => false,
            ]],
        ]]]]],
    ]);
    Fields::refreshFields();

    $field = app(FieldsService::class)->getFieldByHandle('myContentBlock');

    // as ImportConfigController::nestedMappingCols() does for a container column
    $provider = $field->getFieldLayoutProviders()[0];
    $cols = ImportHelper::getDestinationColsForFieldLayout($provider->getFieldLayout(), $field, $provider, 'myContentBlock');
    $col = collect($cols)->firstWhere('handle', 'cbMatrix');

    expect($col['prefixedHandle'])->toBe('myContentBlock[fields][cbMatrix]')
        ->and($col['prefixedHandleForKeepFlag'])->toBe('keepMissingNestedElements[myContentBlock][fields][cbMatrix][__keep__]')
        ->and($col['prefixedHandleAsArray'])->toBe(['myContentBlock', 'fields', 'cbMatrix']);
});
