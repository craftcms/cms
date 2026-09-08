<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;

it('links an entry section by its handle, under its index page', function () {
    $source = ['key' => 'section:abc', 'data' => ['handle' => 'posts']];

    expect(Entry::sourceCpUri($source))->toBe('content/entries/posts')
        ->and(Entry::sourceCpUri($source, 'Media Kit'))->toBe('content/media-kit/posts');
});

it('links singles by the key they share', function () {
    // Every Single shares one source, and so one URL.
    expect(Entry::sourceCpUri(['key' => 'singles']))->toBe('content/entries/singles');
});

it('links an asset volume by its handle', function () {
    expect(Asset::sourceCpUri([
        'key' => 'volume:abc',
        'data' => ['volume-handle' => 'uploads'],
    ]))->toBe('assets/uploads');
});

it('gives a nested asset folder no url of its own', function () {
    // Only a root folder carries a handle; a subfolder sets it to `false` and
    // is reached through the index rather than by a URL that names it.
    expect(Asset::sourceCpUri([
        'key' => 'folder:abc',
        'data' => ['volume-handle' => false],
    ]))->toBeNull();
});

it('gives temporary uploads no url of its own', function () {
    // No volume handle, and the assets route resolves its segment by volume
    // handle — so the nav links it by query instead.
    expect(Asset::sourceCpUri(['key' => 'temp', 'label' => 'Temporary Uploads']))
        ->toBeNull();
});

it('links a user group by its handle', function () {
    expect(User::sourceCpUri([
        'key' => 'group:abc',
        'data' => ['slug' => 'editors'],
    ]))->toBe('users/editors');
});

it('links the built-in user sources by their slugs', function () {
    expect(User::sourceCpUri(['key' => '*', 'data' => ['slug' => 'all']]))
        ->toBe('users/all')
        ->and(User::sourceCpUri(['key' => 'admins', 'data' => ['slug' => 'admins']]))
        ->toBe('users/admins');
});

it('gives a source with no identifying data no url', function () {
    expect(Entry::sourceCpUri(['key' => 'custom:1']))->toBeNull()
        ->and(Asset::sourceCpUri(['key' => 'custom:1']))->toBeNull()
        ->and(User::sourceCpUri(['key' => 'custom:1']))->toBeNull();
});
