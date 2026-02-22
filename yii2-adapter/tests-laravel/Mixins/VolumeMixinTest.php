<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

test('volume keeps canGetProperty compatibility via volume mixin', function() {
    $volume = new Volume();

    expect(method_exists($volume, 'canGetProperty'))->toBeFalse()
        ->and($volume->canGetProperty('name'))->toBeTrue()
        ->and($volume->canGetProperty('fsHandle'))->toBeTrue()
        ->and($volume->canGetProperty('missingProperty'))->toBeFalse();
});

test('volume keeps canSetProperty compatibility via volume mixin', function() {
    $volume = new Volume();

    expect(method_exists($volume, 'canSetProperty'))->toBeFalse()
        ->and($volume->canSetProperty('name'))->toBeTrue()
        ->and($volume->canSetProperty('fsHandle'))->toBeTrue()
        ->and($volume->canSetProperty('missingProperty'))->toBeFalse();
});
