<?php

declare(strict_types=1);

use CraftCms\Cms\Utility\Utilities\ClearCaches;
use Illuminate\Support\Arr;

afterEach(function () {
    ClearCaches::flushState();
});

it('adds cache options lazily', function () {
    $resolved = false;
    ClearCaches::add('plugin', function () use (&$resolved) {
        $resolved = true;

        return [
            'label' => 'Plugin caches',
            'action' => fn () => null,
        ];
    });

    expect($resolved)->toBeFalse();

    $option = ClearCaches::cacheOptions()[0];

    expect(Arr::only($option, ['key', 'label']))->toBe([
        'label' => 'Plugin caches',
        'key' => 'plugin',
    ])->and($resolved)->toBeTrue();
});

it('adds cache tags lazily', function () {
    ClearCaches::addTag('plugin', fn () => 'Plugin caches');

    expect(ClearCaches::tagOptions())->toContain([
        'tag' => 'plugin',
        'label' => 'Plugin caches',
    ]);
});
