<?php

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\PermissionsController;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires login', function () {
    Auth::logout();

    get(cp_url('myaccount/permissions'))->assertRedirect();
    get(cp_url('users/1/permissions'))->assertRedirect();
    postJson(action([PermissionsController::class, 'store']))->assertUnauthorized();
});

test('index is forbidden when edition is not above team', function () {
    get(cp_url('myaccount/permissions'))->assertForbidden();

    Edition::set(Edition::Pro);

    get(cp_url('myaccount/permissions'))->assertOk();
});

it('can store permissions and groups', function () {
    session()->passwordConfirmed();

    $this->withoutExceptionHandling();
    Edition::set(Edition::Pro);

    $user = Auth::user();
    $group = UserGroup::factory()->create();

    expect(UserPermissions::doesUserHavePermission($user->id, 'accessCp'))->toBeFalse();
    expect(UserGroups::getGroupsByUserId($user->id))->toHaveCount(0);

    postJson(action([PermissionsController::class, 'store']), [
        'userId' => $user->id,
        'admin' => false,
        'permissions' => [
            'accessCp',
        ],
        'groups' => [
            $group->id,
        ],
    ])->assertOk();

    expect(UserPermissions::doesUserHavePermission($user->id, 'accessCp'))->toBeTrue();
    expect(UserGroups::getGroupsByUserId($user->id))->toHaveCount(1);
});

test('store validates required userId', function () {
    Edition::set(Edition::Pro);

    postJson(action([PermissionsController::class, 'store']), [])
        ->assertJsonValidationErrors(['userId']);
});

test('store validates userId exists in database', function () {
    Edition::set(Edition::Pro);

    postJson(action([PermissionsController::class, 'store']), [
        'userId' => 99999,
    ])->assertJsonValidationErrors(['userId']);
});

test('store can assign multiple groups', function () {
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = Auth::user();
    $group1 = UserGroup::factory()->create();
    $group2 = UserGroup::factory()->create();

    postJson(action([PermissionsController::class, 'store']), [
        'userId' => $user->id,
        'groups' => [
            $group1->id,
            $group2->id,
        ],
    ])->assertOk();

    expect(UserGroups::getGroupsByUserId($user->id))->toHaveCount(2);
});

test('store can remove all permissions', function () {
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = Auth::user();

    // First assign some permissions
    postJson(action([PermissionsController::class, 'store']), [
        'userId' => $user->id,
        'permissions' => ['accessCp'],
    ])->assertOk();

    // Then remove them
    postJson(action([PermissionsController::class, 'store']), [
        'userId' => $user->id,
        'permissions' => [],
    ])->assertOk();

    expect(UserPermissions::doesUserHavePermission($user->id, 'accessCp'))->toBeFalse();
});

test('store can remove all groups', function () {
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = Auth::user();
    $group = UserGroup::factory()->create();

    // First assign a group
    postJson(action([PermissionsController::class, 'store']), [
        'userId' => $user->id,
        'groups' => [$group->id],
    ])->assertOk();

    // Then remove it
    postJson(action([PermissionsController::class, 'store']), [
        'userId' => $user->id,
        'groups' => [],
    ])->assertOk();

    expect(UserGroups::getGroupsByUserId($user->id))->toHaveCount(0);
});

test('index shows permissions page for own account', function () {
    Edition::set(Edition::Pro);

    $response = get(cp_url('myaccount/permissions'));

    $response->assertOk();
});

test('index shows permissions page for other users', function () {
    Edition::set(Edition::Pro);

    $otherUser = User::find()->one();

    $response = get(cp_url("users/{$otherUser->id}/permissions"));

    $response->assertOk();
});

test('store returns success message', function () {
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = Auth::user();

    postJson(action([PermissionsController::class, 'store']), [
        'userId' => $user->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['message']);
});
