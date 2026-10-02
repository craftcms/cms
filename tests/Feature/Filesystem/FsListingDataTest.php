<?php

declare(strict_types=1);

use CraftCms\Cms\Filesystem\Data\FsListing;

it('normalizes dirname and exposes listing metadata through methods and properties', function () {
    $listing = new FsListing([
        'dirname' => './foo/bar',
        'basename' => 'image.jpg',
        'type' => 'file',
        'fileSize' => 1234,
        'dateModified' => 1710000000,
    ]);

    expect($listing->dirname)->toBe('foo/bar')
        ->and($listing->getDirname())->toBe('foo/bar')
        ->and($listing->basename)->toBe('image.jpg')
        ->and($listing->getBasename())->toBe('image.jpg')
        ->and($listing->type)->toBe('file')
        ->and($listing->getType())->toBe('file')
        ->and($listing->fileSize)->toBe(1234)
        ->and($listing->getFileSize())->toBe(1234)
        ->and($listing->dateModified)->toBe(1710000000)
        ->and($listing->getDateModified())->toBe(1710000000)
        ->and($listing->uri)->toBe('foo/bar/image.jpg')
        ->and($listing->getUri())->toBe('foo/bar/image.jpg')
        ->and($listing->isDir)->toBeFalse()
        ->and($listing->getIsDir())->toBeFalse();
});

it('adjusts listing uris when subtracting and adding prefixes', function () {
    $listing = new FsListing([
        'dirname' => 'foo/bar',
        'basename' => 'image.jpg',
        'type' => 'file',
    ]);

    expect($listing->getAdjustedUri('foo/', 'sub'))->toBe('bar/image.jpg')
        ->and($listing->getAdjustedUri('prefix', 'add'))->toBe('prefix/foo/bar/image.jpg')
        ->and($listing->getAdjustedUri('', 'add'))->toBe('foo/bar/image.jpg');
});

it('reports null file size for directory listings', function () {
    $listing = new FsListing([
        'dirname' => 'foo',
        'basename' => 'bar',
        'type' => 'dir',
        'fileSize' => 9999,
    ]);

    expect($listing->isDir)->toBeTrue()
        ->and($listing->getIsDir())->toBeTrue()
        ->and($listing->fileSize)->toBeNull()
        ->and($listing->getFileSize())->toBeNull();
});
