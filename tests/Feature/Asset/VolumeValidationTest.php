<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\FieldLayout\FieldLayout;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Cms::config()->tempAssetUploadFs = null;
    putenv('CRAFT_TEST_VOLUME_SUBPATH');
});

it('validates id and fieldLayoutId integer attributes', function () {
    $volume = new Volume([
        'id' => 5,
        'fieldLayoutId' => 2,
    ]);

    expect($volume->validate(['id', 'fieldLayoutId']))->toBeTrue()
        ->and($volume->errors()->isEmpty())->toBeTrue();

    $nullable = new Volume;

    expect($nullable->validate(['id', 'fieldLayoutId']))->toBeTrue()
        ->and($nullable->errors()->isEmpty())->toBeTrue();
});

it('trims and validates required and unique names', function () {
    insertVolumeValidationRow([
        'name' => 'Existing Name',
        'handle' => 'existingNameHandle',
    ]);

    $duplicate = new Volume([
        'name' => '  Existing Name  ',
    ]);

    expect($duplicate->validate(['name']))->toBeFalse()
        ->and($duplicate->name)->toBe('Existing Name')
        ->and($duplicate->errors()->has('name'))->toBeTrue();

    $required = new Volume([
        'name' => '   ',
    ]);

    expect($required->validate(['name']))->toBeFalse()
        ->and($required->name)->toBe('')
        ->and($required->errors()->has('name'))->toBeTrue();
});

it('trims and validates handles for required format reserved words and uniqueness', function () {
    insertVolumeValidationRow([
        'name' => 'Existing Handle Name',
        'handle' => 'existingHandle',
    ]);

    $duplicate = new Volume([
        'handle' => '  existingHandle  ',
    ]);

    expect($duplicate->validate(['handle']))->toBeFalse()
        ->and($duplicate->handle)->toBe('existingHandle')
        ->and($duplicate->errors()->has('handle'))->toBeTrue();

    $reserved = new Volume([
        'handle' => 'temp',
    ]);

    expect($reserved->validate(['handle']))->toBeFalse()
        ->and($reserved->errors()->has('handle'))->toBeTrue();

    $invalidFormat = new Volume([
        'handle' => 'bad-handle',
    ]);

    expect($invalidFormat->validate(['handle']))->toBeFalse()
        ->and($invalidFormat->errors()->has('handle'))->toBeTrue();

    $required = new Volume([
        'handle' => '   ',
    ]);

    expect($required->validate(['handle']))->toBeFalse()
        ->and($required->handle)->toBe('')
        ->and($required->errors()->has('handle'))->toBeTrue();
});

it('allows duplicate name and handle values from soft deleted volumes', function () {
    insertVolumeValidationRow([
        'name' => 'Soft Deleted Name',
        'handle' => 'softDeletedHandle',
        'dateDeleted' => now(),
    ]);

    $name = new Volume([
        'name' => 'Soft Deleted Name',
    ]);

    expect($name->validate(['name']))->toBeTrue()
        ->and($name->errors()->has('name'))->toBeFalse();

    $handle = new Volume([
        'handle' => 'softDeletedHandle',
    ]);

    expect($handle->validate(['handle']))->toBeTrue()
        ->and($handle->errors()->has('handle'))->toBeFalse();
});

it('validates fsHandle for required invalid references and internal disks', function () {
    $required = new Volume;

    expect($required->validate(['fsHandle']))->toBeFalse()
        ->and($required->errors()->has('fsHandle'))->toBeTrue();

    $invalid = new Volume([
        'fsHandle' => 'missing-filesystem',
    ]);

    expect($invalid->validate(['fsHandle']))->toBeFalse()
        ->and($invalid->errors()->has('fsHandle'))->toBeTrue();

    config()->set('filesystems.disks.validation-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-validation/validation-disk'),
    ]);

    $valid = new Volume([
        'fsHandle' => 'validation-disk',
    ]);

    expect($valid->validate(['fsHandle']))->toBeTrue()
        ->and($valid->errors()->has('fsHandle'))->toBeFalse();

    config()->set('filesystems.disks.craft-tmp', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-validation/craft-tmp'),
    ]);

    $internal = new Volume([
        'fsHandle' => 'craft-tmp',
    ]);

    expect($internal->validate(['fsHandle']))->toBeFalse()
        ->and($internal->errors()->has('fsHandle'))->toBeTrue();
});

it('validates asset transformer references while allowing unresolved environment variables', function () {
    $invalid = new Volume([
        'assetTransformer' => 'missing-transformer',
    ]);

    expect($invalid->validate(['assetTransformer']))->toBeFalse()
        ->and($invalid->errors()->has('assetTransformer'))->toBeTrue();

    $valid = new Volume([
        'assetTransformer' => 'craft',
    ]);

    expect($valid->validate(['assetTransformer']))->toBeTrue()
        ->and($valid->errors()->has('assetTransformer'))->toBeFalse();

    $unresolved = new Volume([
        'assetTransformer' => '$CRAFT_TEST_ASSET_TRANSFORMER',
    ]);

    expect($unresolved->validate(['assetTransformer']))->toBeTrue()
        ->and($unresolved->errors()->has('assetTransformer'))->toBeFalse();

    putenv('CRAFT_TEST_ASSET_TRANSFORMER=missing-transformer');

    try {
        $resolved = new Volume([
            'assetTransformer' => '$CRAFT_TEST_ASSET_TRANSFORMER',
        ]);

        expect($resolved->validate(['assetTransformer']))->toBeFalse()
            ->and($resolved->errors()->has('assetTransformer'))->toBeTrue();
    } finally {
        putenv('CRAFT_TEST_ASSET_TRANSFORMER');
    }
});

it('rejects the temp upload filesystem target for fsHandle', function () {
    config()->set('filesystems.disks.temp-reserved', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-validation/temp-reserved'),
    ]);
    Cms::config()->tempAssetUploadFs = 'disk:temp-reserved';

    $volumeFs = new Volume([
        'fsHandle' => 'temp-reserved',
    ]);

    expect($volumeFs->validate(['fsHandle']))->toBeFalse()
        ->and($volumeFs->errors()->has('fsHandle'))->toBeTrue();
});

it('requires subpath for shared filesystems and rejects overlapping roots', function () {
    config()->set('filesystems.disks.shared-validation-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/volume-validation/shared-validation-disk'),
    ]);

    insertVolumeValidationRow([
        'name' => 'Existing Shared',
        'handle' => 'existingShared',
        'fs' => 'disk:shared-validation-disk',
        'subpath' => 'foo/bar',
    ]);

    $missingSubpath = new Volume([
        'name' => 'Missing Subpath',
        'handle' => 'missingSubpath',
        'fsHandle' => 'shared-validation-disk',
    ]);

    expect($missingSubpath->validate(['subpath']))->toBeFalse()
        ->and($missingSubpath->errors()->has('subpath'))->toBeTrue();

    $missingEnvironmentSubpath = new Volume([
        'name' => 'Missing Environment Subpath',
        'handle' => 'missingEnvironmentSubpath',
        'fsHandle' => 'shared-validation-disk',
        'subpath' => '$CRAFT_TEST_VOLUME_SUBPATH',
    ]);

    expect($missingEnvironmentSubpath->validate(['subpath']))->toBeFalse()
        ->and($missingEnvironmentSubpath->errors()->has('subpath'))->toBeTrue();

    $overlap = new Volume([
        'name' => 'Overlap',
        'handle' => 'overlap',
        'fsHandle' => 'shared-validation-disk',
        'subpath' => 'foo',
    ]);

    expect($overlap->validate(['subpath']))->toBeFalse()
        ->and($overlap->errors()->has('subpath'))->toBeTrue();

    $valid = new Volume([
        'name' => 'Non Overlap',
        'handle' => 'nonOverlap',
        'fsHandle' => 'shared-validation-disk',
        'subpath' => 'baz/qux',
    ]);

    expect($valid->validate(['subpath']))->toBeTrue()
        ->and($valid->errors()->has('subpath'))->toBeFalse();
});

it('propagates invalid field layout errors to fieldLayout prefixed keys', function () {
    $volume = new Volume([
        'name' => 'Layout Volume',
        'handle' => 'layoutVolume',
    ]);

    $fieldLayout = new FieldLayout([
        'type' => Asset::class,
    ]);
    $fieldLayout->setGeneratedFields([
        [
            'name' => 'Reserved',
            'handle' => 'alt',
            'template' => '{{ object.id }}',
        ],
    ]);

    $volume->setFieldLayout($fieldLayout);

    expect($volume->validate(['fieldLayout']))->toBeFalse()
        ->and($volume->errors()->has('fieldLayout.customFields'))->toBeTrue();
});

function insertVolumeValidationRow(array $overrides = []): void
{
    static $counter = 1;

    DB::table(Table::VOLUMES)->insert(array_merge([
        'name' => "Volume {$counter}",
        'handle' => "volume{$counter}",
        'fs' => 'disk:default-validation-disk',
        'subpath' => null,
        'titleTranslationMethod' => 'site',
        'titleTranslationKeyFormat' => null,
        'altTranslationMethod' => 'none',
        'altTranslationKeyFormat' => null,
        'sortOrder' => $counter,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'dateDeleted' => null,
        'uid' => sprintf('00000000-0000-4000-8000-%012d', $counter),
    ], $overrides));

    $counter++;
}
