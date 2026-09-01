<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Settings\Users\UserGroupsController;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Data\UserGroup as UserGroupData;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires authentication', function () {
    Auth::logout();

    get(action([UserGroupsController::class, 'index']))->assertRedirect();
    get(action([UserGroupsController::class, 'create']))->assertRedirect();
    get(action([UserGroupsController::class, 'edit'], [UserGroup::factory()->create()->id]))->assertRedirect();
    postJson(action([UserGroupsController::class, 'store']))->assertUnauthorized();
    deleteJson(action([UserGroupsController::class, 'destroy'], [UserGroup::factory()->create()->id]))->assertUnauthorized();
});

it('requires admin changes', function () {
    Cms::config()->allowAdminChanges = false;
    Edition::set(Edition::Pro);

    // Read only
    get(action([UserGroupsController::class, 'edit'], [UserGroup::factory()->create()->id]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/users/groups/Edit')
            ->where('deleteAction', null)
            ->where('form.nodes', function (Collection $nodes): bool {
                $controls = $nodes->pluck('control')->filter();

                return $controls->isNotEmpty()
                    && $controls->every(fn (array $control): bool => $control['mode'] === 'readOnly');
            }));

    // Not allowed
    get(action([UserGroupsController::class, 'create']))->assertForbidden();
    postJson(action([UserGroupsController::class, 'store']))->assertForbidden();
    deleteJson(action([UserGroupsController::class, 'destroy'], [UserGroup::factory()->create()->id]))->assertForbidden();
});

test('create requires pro edition', function () {
    Edition::set(Edition::Team);

    config()->set('app.debug', false);

    get(action([UserGroupsController::class, 'create']))->assertNotFound();

    Edition::set(Edition::Pro);

    get(action([UserGroupsController::class, 'create']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/users/groups/Edit')
            ->where('form.values.id', null)
            ->where('form.values.permissions', [])
            ->where('submit.method', 'post')
            ->where('form.nodes', fn (Collection $nodes): bool => $nodes
                ->contains(fn (array $node): bool => ($node['control']['path'] ?? null) === ['handle']
                    && ($node['control']['props']['source'] ?? null) === ['name'])));
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
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/users/groups/Edit')
            ->where('form.values.id', $group->id)
            ->where('form.values.name', $group->name)
            ->where('elevatedFields', ['permissions'])
            ->where('deleteAction.url', action([UserGroupsController::class, 'destroy'], $group->id)));
});

test('store validates on unique handle and name', function () {
    Edition::set(Edition::Pro);

    $group = UserGroup::factory()->create([
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ]);

    post(action([UserGroupsController::class, 'store']), [
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ])
        ->assertSessionHasErrors(['name', 'handle']);

    // Existing doesn't trigger unique validation
    postJson(action([UserGroupsController::class, 'store']), [
        'id' => $group->id,
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ])->assertOk();
});

it('can delete a group', function () {
    Edition::set(Edition::Pro);

    UserGroups::saveGroup($group = new UserGroupData([
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ]));

    expect(UserGroup::count())->toBe(1);

    deleteJson(action([UserGroupsController::class, 'destroy'], [$group->id]))->assertOk();

    expect(UserGroup::count())->toBe(0);
});
