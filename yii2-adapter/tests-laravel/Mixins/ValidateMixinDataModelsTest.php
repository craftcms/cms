<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\FolderCriteria;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Filesystem\Data\FsListing;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

test('volume folder keeps hasErrors compatibility helpers via validate mixin', function() {
    $folder = new VolumeFolder([
        'parentId' => 'not-an-int',
    ]);

    expect($folder->validate(['parentId']))->toBeFalse()
        ->and($folder->hasErrors('parentId'))->toBeTrue()
        ->and($folder->hasErrors())->toBeTrue();
});

test('fs listing keeps hasErrors compatibility helpers via validate mixin', function() {
    $listing = new FsListing([
        'dirname' => 'foo',
        'basename' => 'bar',
        'type' => 'file',
    ]);

    expect($listing->hasErrors())->toBeFalse();

    $listing->errors()->add('basename', 'Invalid basename.');

    expect($listing->hasErrors('basename'))->toBeTrue()
        ->and($listing->hasErrors())->toBeTrue();
});

test('folder criteria keeps hasErrors compatibility helpers via validate mixin', function() {
    $criteria = new FolderCriteria([
        'id' => 'abc',
        'parentId' => 'def',
        'sourceId' => 'ghi',
    ]);

    expect($criteria->validate(['id', 'parentId', 'sourceId']))->toBeFalse()
        ->and($criteria->hasErrors('id'))->toBeTrue()
        ->and($criteria->hasErrors('parentId'))->toBeTrue()
        ->and($criteria->hasErrors('sourceId'))->toBeTrue()
        ->and($criteria->hasErrors())->toBeTrue();
});
