<?php

declare(strict_types=1);

use CraftCms\DependencyAwareCache\Dependency\TagDependency;
use CraftCms\Yii2Adapter\Cache;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

test('values set with a native dependency are read back until it is invalidated', function() {
    $cache = new Cache(['keyPrefix' => 'adapter-test']);

    $cache->set('probe', 'hello', 60, new TagDependency('adapter-cache-test'));

    expect($cache->get('probe'))->toBe('hello')
        ->and($cache->exists('probe'))->toBeTrue()
        ->and($cache->multiGet(['probe', 'missing']))->toBe(['probe' => 'hello', 'missing' => false]);

    TagDependency::invalidate('adapter-cache-test');

    expect($cache->get('probe'))->toBeFalse()
        ->and($cache->exists('probe'))->toBeFalse()
        ->and($cache->multiGet(['probe']))->toBe(['probe' => false]);
});
