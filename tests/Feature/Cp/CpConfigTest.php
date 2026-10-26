<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

it('reports the password timeout as the elevated session duration', function (int $passwordTimeout, int $expected) {
    actingAs(User::findOne());
    config()->set('auth.password_timeout', $passwordTimeout);

    expect(Cp::config()->get('elevatedSessionDuration'))->toBe($expected);
})->with([
    'enabled' => [42, 42],
    'disabled' => [-1, 0],
]);
