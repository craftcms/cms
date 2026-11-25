<?php

declare(strict_types=1);

use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('getAttributes', function() {
    $user = new User(['username' => 'ada', 'email' => 'ada@example.com']);

    expect($user->getAttributes())->toMatchArray(['username' => 'ada', 'email' => 'ada@example.com'])
        ->and($user->getAttributes(['username', 'email'], ['email']))->toBe(['username' => 'ada']);
});
