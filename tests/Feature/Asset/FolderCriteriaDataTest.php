<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\FolderCriteria;

it('keeps folder criteria defaults and accepts common filter properties', function () {
    $criteria = new FolderCriteria([
        'id' => [1, 2],
        'parentId' => ':empty:',
        'volumeId' => 'not 3',
        'name' => 'images',
        'path' => 'foo/bar',
        'uid' => 'uid-123',
    ]);

    expect($criteria->order)->toBe('name asc')
        ->and($criteria->id)->toBe([1, 2])
        ->and($criteria->parentId)->toBe(':empty:')
        ->and($criteria->volumeId)->toBe('not 3')
        ->and($criteria->name)->toBe('images')
        ->and($criteria->path)->toBe('foo/bar')
        ->and($criteria->uid)->toBe('uid-123');
});

it('validates integer-only folder criteria attributes', function () {
    $criteria = new FolderCriteria([
        'id' => 'abc',
        'parentId' => 'def',
        'sourceId' => 'ghi',
    ]);

    expect($criteria->validate(['id', 'parentId', 'sourceId']))->toBeFalse()
        ->and($criteria->errors()->has('id'))->toBeTrue()
        ->and($criteria->errors()->has('parentId'))->toBeTrue()
        ->and($criteria->errors()->has('sourceId'))->toBeTrue()
        ->and($criteria->errors()->isNotEmpty())->toBeTrue();

    $valid = new FolderCriteria([
        'id' => 1,
        'parentId' => 2,
        'sourceId' => 3,
        'offset' => 4,
        'limit' => 5,
    ]);

    expect($valid->validate(['id', 'parentId', 'sourceId', 'offset', 'limit']))->toBeTrue()
        ->and($valid->errors()->isEmpty())->toBeTrue();
});
