<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

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
    $id = UserModel::factory()->create()->id;

    expect(User::find()->id($id)->select('elements.id')->scalar())->toBe($id)
        ->and(User::find()->id($id)->scalar())->toBe(array_values(User::find()->id($id)->asArray()->one())[0]);
});

test('addOrderBy', function() {
    // No exception
    $this->expectNotToPerformAssertions();
    User::find()->select('id')->addOrderBy(['id', SORT_ASC]);
});
