<?php

declare(strict_types=1);

use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Models\UserPermission;

it('can query for admins', function () {
    expect(userQuery()->admin()->count())->toBe(1);
    expect(userQuery()->admin(false)->count())->toBe(0);
});

it('can query for permissions', function (string $directName, string $groupName, string $directQuery, string $groupQuery) {
    CraftCms\Cms\User\Models\User::factory()->create([
        'admin' => false,
    ]);

    $user = CraftCms\Cms\User\Models\User::factory()->create([
        'admin' => false,
    ]);

    $userGroup = UserGroup::factory()->create();
    $userGroup->users()->attach($user);

    $canFoo = UserPermission::factory()->create(['name' => $directName]);
    $canBar = UserPermission::factory()->create(['name' => $groupName]);

    $user->permissions()->attach($canFoo);
    $userGroup->permissions()->attach($canBar);

    expect(userQuery()->count())->toBe(3);

    // Default admin counts + user with permission
    expect(userQuery()->can($directQuery)->count())->toBe(2);

    // Default admin counts + user with permission in group
    expect(userQuery()->can($groupQuery)->count())->toBe(2);
})->with([
    'legacy names' => ['foo', 'bar', 'foo', 'bar'],
    'canonical names with case-insensitive queries' => ['accessCp', 'useCraftMcp', 'ACCESSCP', 'usecraftmcp'],
]);
