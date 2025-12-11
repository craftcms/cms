<?php

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\ActivateController;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('requires login', function () {
    postJson(action([ActivateController::class, 'activate']))->assertUnauthorized();
    postJson(action([ActivateController::class, 'deactivate']))->assertUnauthorized();
});

test('activate requires administrateUsers permission and activates users', function () {
    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    $user2 = User::factory()->create([
        'active' => false,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'activate']), [
        'userId' => $user2->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'viewUsers',
        'editUsers',
        'administrateUsers',
    ]);

    postJson(action([ActivateController::class, 'activate']), [
        'userId' => $user2->id,
    ])->assertOk();

    expect($user2->fresh()->active)->toBeTrue();
});

test('deactivate requires administrateUsers permission and deactivates users', function () {
    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    $user2 = User::factory()->create([
        'active' => true,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'deactivate']), [
        'userId' => $user2->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'viewUsers',
        'editUsers',
        'administrateUsers',
    ]);

    postJson(action([ActivateController::class, 'deactivate']), [
        'userId' => $user2->id,
    ])->assertOk();

    expect($user2->fresh()->active)->toBeFalse();
});
