<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Notifications\ActivationNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\patchJson;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires login', function () {
    Auth::logout();

    get(cp_url('myaccount/permissions'))->assertRedirect();
    get(cp_url('users/1/permissions'))->assertRedirect();
    patchJson(cp_url('myaccount/permissions'))->assertUnauthorized();
    patchJson(cp_url('users/1/permissions'))->assertUnauthorized();
});

test('index is forbidden when edition is not above team', function () {
    Edition::set(Edition::Solo);

    get(cp_url('myaccount/permissions'))->assertForbidden();

    Edition::set(Edition::Pro);

    get(cp_url('myaccount/permissions'))->assertOk();
});

it('can store permissions and groups', function () {
    session()->passwordConfirmed();

    $this->withoutExceptionHandling();
    Edition::set(Edition::Pro);

    $user = currentUser();
    $group = UserGroup::factory()->create();

    expect(UserPermissions::doesUserHavePermission($user->id, 'accessCp'))->toBeFalse();
    expect(UserGroups::getGroupsByUserId($user->id))->toHaveCount(0);

    patchJson(cp_url('myaccount/permissions'), [
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

test('update validates group ids', function () {
    Edition::set(Edition::Pro);

    patchJson(cp_url('myaccount/permissions'), [
        'groups' => [99999],
    ])->assertJsonValidationErrors(['groups.0']);
});

test('store can assign multiple groups', function () {
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = currentUser();
    $group1 = UserGroup::factory()->create();
    $group2 = UserGroup::factory()->create();

    patchJson(cp_url('myaccount/permissions'), [
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

    $user = currentUser();

    patchJson(cp_url('myaccount/permissions'), [
        'permissions' => ['accessCp'],
    ])->assertOk();

    patchJson(cp_url('myaccount/permissions'), [
        'permissions' => [],
    ])->assertOk();

    expect(UserPermissions::doesUserHavePermission($user->id, 'accessCp'))->toBeFalse();
});

test('store can remove all groups', function () {
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = currentUser();
    $group = UserGroup::factory()->create();

    patchJson(cp_url('myaccount/permissions'), [
        'groups' => [$group->id],
    ])->assertOk();

    patchJson(cp_url('myaccount/permissions'), [
        'groups' => [],
    ])->assertOk();

    expect(UserGroups::getGroupsByUserId($user->id))->toHaveCount(0);
});

test('index shows permissions page for own account', function () {
    Edition::set(Edition::Pro);

    $user = currentUser();
    $group = UserGroup::factory()->create([
        'name' => 'Editors',
        'handle' => 'editors',
        'description' => 'Can edit content.',
    ]);

    Users::assignUserToGroups($user->id, [$group->id]);
    UserPermissions::saveGroupPermissions($group->id, ['accessCp']);
    UserPermissions::saveUserPermissions($user->id, ['accessSiteWhenSystemIsOff']);

    $response = get(cp_url('myaccount/permissions'));

    $response
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Permissions')
            ->where('user.isCurrent', true)
            ->where('groups', fn ($groups) => collect($groups)->contains(
                fn (array $option) => $option['id'] === $group->id &&
                    $option['name'] === 'Editors' &&
                    $option['handle'] === 'editors' &&
                    $option['description'] === 'Can edit content.' &&
                    collect($option['permissions'])->all() === ['accessCp'],
            ))
            ->where('currentGroupIds', fn ($groupIds) => collect($groupIds)->all() === [$group->id])
            ->where('directPermissions', fn ($permissions) => collect($permissions)->all() === ['accessSiteWhenSystemIsOff'])
            ->where('inheritedPermissions', fn ($permissions) => collect($permissions)->all() === ['accessCp'])
            ->has('permissions')
            // A list, not an object keyed by screen name: the shell hides the
            // secondary nav when it can't count the items.
            ->where('subnav.0.label', t('Profile'))
            ->has('details'));
});

test('index shows permissions page for other users', function () {
    Edition::set(Edition::Pro);

    $otherUser = UserModel::factory()->create([
        'active' => false,
        'pending' => false,
    ]);

    $response = get(cp_url("users/{$otherUser->id}/permissions"));

    $response
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Permissions')
            ->where('user.id', $otherUser->id)
            ->where('user.isCurrent', false)
            ->where('can.canSendActivationEmail', true));
});

test('store returns success message', function () {
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = currentUser();

    patchJson(cp_url('myaccount/permissions'))
        ->assertOk()
        ->assertJsonStructure(['message']);
});

test('store sends activation email and marks inactive user as pending', function () {
    Notification::fake();
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $inactiveUser = UserModel::factory()->create([
        'active' => false,
        'pending' => false,
    ]);

    patchJson(cp_url("users/{$inactiveUser->id}/permissions"), [
        'admin' => true,
        'sendActivationEmail' => true,
    ])->assertOk();

    expect($inactiveUser->fresh()->pending)->toBeTrue();
});

test('store can send activation email through moderateUsers permission', function () {
    Notification::fake();
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = UserModel::factory()
        ->withPermissions(['accessCp', 'viewUsers', 'editUsers', 'assignUserPermissions', 'moderateUsers'])
        ->create();
    $inactiveUser = UserModel::factory()->create([
        'active' => false,
        'pending' => false,
    ]);

    actingAs($user->asElement());

    patchJson(cp_url("users/{$inactiveUser->id}/permissions"), [
        'sendActivationEmail' => true,
    ])->assertOk();

    expect($inactiveUser->fresh()->pending)->toBeTrue();

    Notification::assertSentTimes(ActivationNotification::class, 1);
});

test('update does not persist inherited group permissions as direct user permissions', function () {
    session()->passwordConfirmed();
    Edition::set(Edition::Pro);

    $user = currentUser();
    $group = UserGroup::factory()->create();

    UserPermissions::saveGroupPermissions($group->id, ['accessCp']);

    patchJson(cp_url('myaccount/permissions'), [
        'groups' => [$group->id],
        'permissions' => [],
    ])->assertOk();

    $permissionIds = DB::table(Table::USERPERMISSIONS)
        ->where('name', 'accessCp')
        ->pluck('id');

    expect(UserPermissions::doesUserHavePermission($user->id, 'accessCp'))->toBeTrue();
    expect(DB::table(Table::USERPERMISSIONS_USERS)
        ->where('userId', $user->id)
        ->whereIn('permissionId', $permissionIds)
        ->exists())->toBeFalse();
});
