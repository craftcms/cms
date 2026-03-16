<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

test('cachedResult', function() {
    $query = User::find();
    expect($query->getCachedResult())->toBeNull();

    $query->setCachedResult([]);

    expect($query->getCachedResult())->toBe([]);

    $query->clearCachedResult();

    expect($query->getCachedResult())->toBeNull();
});

test('collect', function() {
    expect(User::find()->collect())->toBeInstanceOf(ElementCollection::class);
});

test('scalar', function() {
    expect(User::find()->select('id')->scalar())->toBe(\CraftCms\Cms\User\Models\User::first()->id);
});

test('addOrderBy', function() {
    expect(User::find()->select('id')->addOrderBy(['id', SORT_ASC])->first()->id)->toBe(\CraftCms\Cms\User\Models\User::first()->id);
});
