<?php

declare(strict_types=1);

use craft\models\GqlSchema;
use craft\models\GqlToken;
use CraftCms\Yii2Adapter\Tests\TestCase;

uses(TestCase::class);

test('legacy gql schema keeps getErrors compatibility helpers via validate mixin', function() {
    $schema = new GqlSchema();

    expect($schema->validate(['name']))->toBeFalse()
        ->and($schema->getErrors('name'))->not->toBeEmpty()
        ->and($schema->hasErrors('name'))->toBeTrue();
});

test('legacy gql token keeps getErrors compatibility helpers via validate mixin', function() {
    $token = new GqlToken();

    expect($token->validate(['name']))->toBeFalse()
        ->and($token->getErrors('name'))->not->toBeEmpty()
        ->and($token->hasErrors('name'))->toBeTrue();
});
