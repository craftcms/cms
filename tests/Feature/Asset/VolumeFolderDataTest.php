<?php

declare(strict_types=1);

use craft\models\VolumeFolder as LegacyVolumeFolder;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;

it('keeps the legacy VolumeFolder model alias wired to the new data class', function () {
    $folder = new LegacyVolumeFolder;

    expect($folder)->toBeInstanceOf(VolumeFolder::class);
});

it('validates folder IDs and keeps hasErrors compatibility helpers', function () {
    $invalidParentId = new VolumeFolder([
        'parentId' => 'not-an-int',
    ]);

    expect($invalidParentId->validate(['parentId']))->toBeFalse()
        ->and($invalidParentId->hasErrors('parentId'))->toBeTrue()
        ->and($invalidParentId->hasErrors())->toBeTrue();

    $folder = new VolumeFolder([
        'id' => 1,
        'parentId' => 2,
        'volumeId' => 3,
    ]);

    expect($folder->validate(['id', 'parentId', 'volumeId']))->toBeTrue()
        ->and($folder->errors()->isEmpty())->toBeTrue()
        ->and($folder->hasErrors())->toBeFalse();
});

it('resolves the temporary volume when no volume ID is set', function () {
    $folder = new VolumeFolder;

    expect($folder->getVolume())->toBeInstanceOf(Volume::class)
        ->and($folder->getSourcePathInfo())->toBeNull();
});

it('throws for invalid volume IDs', function () {
    $folder = new VolumeFolder([
        'volumeId' => 999999,
    ]);

    expect(fn () => $folder->getVolume())
        ->toThrow(RuntimeException::class, 'Invalid volume ID: 999999');
});

it('treats manually assigned children as folder descendants', function () {
    $parent = new VolumeFolder(['name' => 'Parent']);
    $child = new VolumeFolder(['name' => 'Child']);

    $parent->setChildren([$child]);

    expect($parent->getHasChildren())->toBeTrue()
        ->and($parent->getChildren())->toHaveCount(1)
        ->and((string) $parent)->toBe('Parent');
});
