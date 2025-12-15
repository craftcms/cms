<?php

use CraftCms\Cms\User\Models\UserGroup;

it('can query users in groups', function () {
    $userGroup = UserGroup::factory()->create();
    $user = \CraftCms\Cms\User\Models\User::factory()->create();
    $userGroup->users()->attach($user);

    expect(userQuery()->count())->toBe(2);

    expect(userQuery()->groupId($userGroup->id)->count())->toBe(1);
    expect(userQuery()->group($userGroup->handle)->count())->toBe(1);
    expect(userQuery()->group([$userGroup->handle])->count())->toBe(1);
    expect(userQuery()->group(['not', $userGroup->handle])->count())->toBe(1);
});
