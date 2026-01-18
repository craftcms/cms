<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Queries\UserQuery;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

it('sorts by username by default', function () {
    UserModel::firstOrFail()->update(['username' => 'z']);
    $firstUser = UserModel::firstOrFail();
    $secondUser = UserModel::factory()->create(['username' => 'a']);

    expect(userQuery()->pluck('id')->all())->toBe([$secondUser->id, $firstUser->id]);
});

test('gets active users first', function () {
    $inactive = UserModel::factory()->createElement([
        'active' => false,
        'pending' => false,
        'username' => 'john',
        'email' => 'john@example.com',
    ]);

    $active = UserModel::factory()->createElement([
        'active' => true,
        'pending' => false,
        'username' => 'john',
        'email' => 'john@example.com',
    ]);

    expect(userQuery()->email('john@example.com')->status(null)->pluck('id')->first())->toBe($active->id);

    // Even when sorting on something else
    expect(userQuery()->email('john@example.com')->status(null)->orderBy('email')->pluck('id')->first())->toBe($active->id);
});

test('gets pending users first', function () {
    $nonPending = UserModel::factory()->createElement([
        'active' => false,
        'pending' => false,
        'username' => 'john',
        'email' => 'john@example.com',
    ]);

    $pending = UserModel::factory()->createElement([
        'active' => false,
        'pending' => true,
        'username' => 'john',
        'email' => 'john@example.com',
    ]);

    expect(userQuery()->email('john@example.com')->status(null)->pluck('id')->first())->toBe($pending->id);

    // Even when sorting on something else
    expect(userQuery()->email('john@example.com')->status(null)->orderBy('email')->pluck('id')->first())->toBe($pending->id);
});

it('can query by status', function (string $status, array $attributes, int $expectedCount) {
    Cms::config()->cooldownDuration = 0;

    UserModel::factory()->create($attributes);

    expect(userQuery()->status($status)->count())->toBe($expectedCount);
})->with([
    [User::STATUS_INACTIVE, ['active' => false, 'pending' => false], 1],
    [User::STATUS_ACTIVE, ['active' => true, 'suspended' => false], 2], // Default user = 2
    [User::STATUS_PENDING, ['pending' => true], 1],
    [UserQuery::STATUS_CREDENTIALED, ['pending' => true], 2], // Default user = 2
    [User::STATUS_SUSPENDED, ['suspended' => true], 1],
    [User::STATUS_LOCKED, ['locked' => true], 1],
]);
