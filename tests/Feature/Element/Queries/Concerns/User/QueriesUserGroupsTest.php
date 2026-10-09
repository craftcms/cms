<?php

use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;

it('can query users in groups', function () {
    $userGroup = UserGroup::factory()->create();
    $user = User::factory()->create();
    $userGroup->users()->attach($user);

    expect(userQuery()->count())->toBe(2);

    expect(userQuery()->groupId($userGroup->id)->count())->toBe(1);
    expect(userQuery()->group($userGroup->handle)->count())->toBe(1);
    expect(userQuery()->group([$userGroup->handle])->count())->toBe(1);
    expect(userQuery()->group(['not', $userGroup->handle])->count())->toBe(1);
    expect(userQuery()->group('notavalidhandle')->count())->toBe(0);
});

it('can eager-load user groups', function () {
    $userGroup = UserGroup::factory()->create();
    $user = User::factory()->create();
    $userGroup->users()->attach($user);

    $user = userQuery()->id($user->id)->withGroups()->one();

    expect($user->getGroups())->toHaveCount(1)
        ->and($user->getGroups()[0]->id)->toBe($userGroup->id);
});
