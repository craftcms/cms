<?php

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\User\Elements\User;

it('can query users by user properties', function (string $property, mixed $value, mixed $param, int $expectedCount) {
    if ($property === 'email') {
        if ($value === 'value,with,comma') {
            $this->expectNotToPerformAssertions();

            return;
        }

        $value .= '@example.com';
        $param .= '@example.com';
    }

    \CraftCms\Cms\User\Models\User::factory()->create([$property => $value]);

    expect(userQuery()->$property($param)->count())->toBe($expectedCount);
})->with([
    'username',
    'email',
    'fullName',
    'firstName',
    'lastName',
])->with([
    ['shinybrad', 'shinybrad', 1],
    ['shinybrad', 'ShInYbRaD', 1], // Case insensitive
    ['value,with,comma', 'value,with,comma', 1],
]);

it('can query users that have photos', function () {
    expect(userQuery()->hasPhoto()->count())->toBe(0);
    expect(userQuery()->hasPhoto(false)->count())->toBe(1);

    \CraftCms\Cms\User\Models\User::first()->update([
        'photoId' => Asset::factory()->create()->id,
    ]);

    expect(userQuery()->hasPhoto()->count())->toBe(1);
    expect(userQuery()->hasPhoto(false)->count())->toBe(0);
});

it('can query by last login date', function (mixed $param, int $expectedCount) {
    \CraftCms\Cms\User\Models\User::factory()->create(['lastLoginDate' => now()->subDay()]);
    \CraftCms\Cms\User\Models\User::factory()->create(['lastLoginDate' => now()]);
    \CraftCms\Cms\User\Models\User::factory()->create(['lastLoginDate' => now()->addDay()]);

    expect(userQuery()->lastLoginDate($param)->count())->toBe($expectedCount);
})->with([
    ['now', 1],
    ['> now', 1],
    ['< now', 1],
    [':empty:', 1], // default user
]);
