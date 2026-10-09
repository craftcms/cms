<?php

declare(strict_types=1);

use CraftCms\Cms\Providers\AppServiceProvider;
use CraftCms\Cms\User\Validation\Rules\UserPasswordRule;

it('keeps application password defaults aligned with the user password rule', function () {
    expect(AppServiceProvider::$minPasswordLength)->toBe(UserPasswordRule::MIN_PASSWORD_LENGTH)
        ->and(AppServiceProvider::$maxPasswordLength)->toBe(UserPasswordRule::MAX_PASSWORD_LENGTH);
});

it('validates against the application password length defaults', function (string $password, bool $expected) {
    $rule = new UserPasswordRule;
    $valid = true;

    $rule->validate('newPassword', $password, function () use (&$valid) {
        $valid = false;
    });

    expect($valid)->toBe($expected);
})->with([
    'empty values are ignored by this rule' => ['', true],
    'below minimum' => ['1234567', false],
    'minimum length' => ['12345678', true],
    'maximum length' => [str_repeat('a', UserPasswordRule::MAX_PASSWORD_LENGTH), true],
    'above maximum' => [str_repeat('a', UserPasswordRule::MAX_PASSWORD_LENGTH + 1), false],
]);
