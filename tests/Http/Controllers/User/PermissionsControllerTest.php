<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\PermissionsController;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::firstOrFail());
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
    Cms::config()->elevatedSessionDuration(0);

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
