<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Settings\UserGroupsController;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Data\UserGroup as UserGroupData;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->firstOrFail());
});

it('requires authentication', function () {
    Auth::logout();

    get(action([UserGroupsController::class, 'index']))->assertRedirect();
    get(action([UserGroupsController::class, 'create']))->assertRedirect();
    get(action([UserGroupsController::class, 'edit'], [UserGroup::factory()->create()->id]))->assertRedirect();
    postJson(action([UserGroupsController::class, 'store']))->assertUnauthorized();
    postJson(action([UserGroupsController::class, 'destroy']))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;
    Edition::set(Edition::Pro);

    // Read only
    get(action([UserGroupsController::class, 'edit'], [UserGroup::factory()->create()->id]))->assertSee(t('Changes to these settings aren’t permitted in this environment.'));

    // Not allowed
    get(action([UserGroupsController::class, 'create']))->assertForbidden();
    postJson(action([UserGroupsController::class, 'store']))->assertForbidden();
    postJson(action([UserGroupsController::class, 'destroy']))->assertForbidden();
});

test('create requires pro edition', function () {
    Edition::set(Edition::Team);

    get(action([UserGroupsController::class, 'create']))->assertNotFound();

    Edition::set(Edition::Pro);

    get(action([UserGroupsController::class, 'create']))->assertOk();
});

test('index redirects to team permissions page when edition is team', function () {
    Edition::set(Edition::Team);

    get(action([UserGroupsController::class, 'index']))
        ->assertRedirect(action([UserGroupsController::class, 'edit'], UserGroups::getTeamGroup()->id));
});

test('edit renders team page when edition is team', function () {
    $group = UserGroup::factory()->create();

    Edition::set(Edition::Team);

    get(action([UserGroupsController::class, 'edit'], $group->id))
        ->assertSee('User Permissions')
        ->assertDontSee($group->name);
});

test('edit renders user group page when edition is pro or higher', function () {
    $group = UserGroup::factory()->create();

    Edition::set(Edition::Pro);

    get(action([UserGroupsController::class, 'edit'], $group->id))
        ->assertSee($group->name);
});

test('store validates on unique handle and name', function () {
    Edition::set(Edition::Pro);

    $group = UserGroup::factory()->create([
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ]);

    postJson(action([UserGroupsController::class, 'store']), [
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ])
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('handle');

    // Existing doesn't trigger unique validation
    postJson(action([UserGroupsController::class, 'store']), [
        'id' => $group->id,
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ])->assertOk();
});

it('can delete a group', function () {
    Edition::set(Edition::Pro);

    UserGroups::saveGroup($group = UserGroupData::from([
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ]));

    expect(UserGroup::count())->toBe(1);

    postJson(action([UserGroupsController::class, 'destroy']), [
        'id' => $group->id,
    ])->assertOk();

    expect(UserGroup::count())->toBe(0);
});
