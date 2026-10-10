<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Settings\Users\UserGroupsController;
use CraftCms\Cms\Http\Middleware\RequireEdition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Data\UserGroup as UserGroupData;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\UserGroupPermissionsSaved;
use CraftCms\Cms\User\Events\UserGroupSaved;
use CraftCms\Cms\User\Events\UserGroupSaving;
use CraftCms\Cms\User\Models\UserGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
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
            ->where('ui.nodes', function (Collection $nodes): bool {
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
            ->where('ui.values.id', null)
            ->where('ui.values.permissions', [])
            ->where('submit.method', 'post')
            ->where('crumbs.3.label', 'New user group')
            ->where('crumbs.3.href', null)
            ->where('ui.nodes', fn (Collection $nodes): bool => $nodes
                ->contains(fn (array $node): bool => ($node['control']['path'] ?? null) === ['handle']
                    && ($node['control']['props']['source'] ?? null) === ['name'])));
});

test('index redirects to team permissions page when edition is team', function () {
    Edition::set(Edition::Team);

    get(action([UserGroupsController::class, 'index']))
        ->assertRedirect(action([UserGroupsController::class, 'edit'], UserGroups::getTeamGroup()->id));
});

test('index lists linked groups and copyable handles with actions allowed by admin changes', function (bool $allowAdminChanges) {
    Edition::set(Edition::Pro);
    Cms::config()->allowAdminChanges = $allowAdminChanges;
    $group = UserGroup::factory()->create([
        'name' => 'Editors',
        'handle' => 'editors',
    ]);

    $response = get(action([UserGroupsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Ui'));

    $table = collect(flattenUiNodes($response->inertiaProps('ui.nodes')))
        ->firstWhere('component', 'craft:admin-table');
    expect($table['props'])->toMatchArray([
        'deletable' => $allowAdminChanges,
        'bulkDeleteUrl' => null,
        'createUrl' => $allowAdminChanges ? action([UserGroupsController::class, 'create']) : null,
    ]);

    $row = collect($table['props']['rows'])->firstWhere('id', $group->id);
    expect($row['name'])->toMatchArray([
        'label' => 'Editors',
        'url' => action([UserGroupsController::class, 'edit'], $group->id),
    ]);

    $handle = new Crawler($row['handle']['html'])->filter('craft-copy-attribute');
    expect($handle->text())->toBe('editors')
        ->and($handle->attr('value'))->toBe('editors');

    if ($allowAdminChanges) {
        expect($row['_deleteUrl'])->toBe(action([UserGroupsController::class, 'destroy'], $group->id))
            ->and($row['_deleteConfirmMessage'])->toBe('Are you sure you want to delete "Editors"?');

        return;
    }

    expect($row)->not->toHaveKeys(['_deleteUrl', '_deleteConfirmMessage']);
})->with([true, false]);

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
            ->where('ui.values.id', $group->id)
            ->where('ui.values.name', $group->name)
            ->where('elevatedFields', ['permissions'])
            ->where('deleteAction.url', action([UserGroupsController::class, 'destroy'], $group->id)));
});

test('store validates on unique handle and name', function () {
    Edition::set(Edition::Pro);
    config()->set('auth.password_timeout', 300);
    $this->withSession(['auth.password_confirmed_at' => now()->subMinutes(10)->unix()]);

    $group = UserGroup::factory()->create([
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ]);

    post(action([UserGroupsController::class, 'store']), [
        'name' => 'A new group',
        'handle' => 'anewgroup',
        'permissions' => ['accessCp'],
    ])
        ->assertSessionHasErrors(['name', 'handle']);

    // Existing doesn't trigger unique validation
    postJson(action([UserGroupsController::class, 'store']), [
        'id' => $group->id,
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ])->assertOk();

    $response = postJson(action([UserGroupsController::class, 'store']), [
        'name' => 'Another group',
        'handle' => 'anotherGroup',
        'permissions' => ['viewUsers', 'editUsers', 'assignNewUserGroup'],
    ]);

    $newGroup = UserGroup::where('handle', 'anotherGroup')->firstOrFail();
    $response->assertOk()
        ->assertJsonPath('group.id', $newGroup->id)
        ->assertJsonPath('group.uid', $newGroup->uid)
        ->assertJsonPath('group.name', 'Another group')
        ->assertJsonPath('group.handle', 'anotherGroup');
    expect(UserPermissions::getPermissionsByGroupId($newGroup->id)->all())->toEqualCanonicalizing(['viewUsers', 'editUsers', "assignUserGroup:$newGroup->uid"]);
});

it('can delete a group', function (bool $json) {
    Edition::set(Edition::Pro);

    UserGroups::saveGroup($group = new UserGroupData([
        'name' => 'A new group',
        'handle' => 'anewgroup',
    ]));

    expect(UserGroup::count())->toBe(1);

    $url = action([UserGroupsController::class, 'destroy'], [$group->id]);

    if ($json) {
        deleteJson($url)->assertOk();
    } else {
        delete($url)->assertRedirect(action([UserGroupsController::class, 'index']));
    }

    expect(UserGroup::count())->toBe(0);

    $response = get(action([UserGroupsController::class, 'index']))->assertOk();
    $table = collect(flattenUiNodes($response->inertiaProps('ui.nodes')))
        ->firstWhere('component', 'craft:admin-table');

    expect($table['props']['rows'])->toBe([])
        ->and($table['props']['emptyMessage'])->toBe('No groups exist yet.');
})->with([true, false]);

it('checks permission elevation before saving group metadata', function (array $initialPermissions, array $permissions, bool $confirmed, bool $allowed, bool $team = false) {
    Edition::set($team ? Edition::Team : Edition::Pro);
    if ($team) {
        $this->withoutMiddleware(RequireEdition::class);
    }

    config()->set('auth.password_timeout', 300);
    $this->withSession(['auth.password_confirmed_at' => $confirmed ? now()->unix() : now()->subMinutes(10)->unix()]);

    $group = $team
        ? UserGroup::findOrFail(UserGroups::getTeamGroup()->id)
        : UserGroup::factory()->create()->refresh();
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->rebuild();
    UserPermissions::saveGroupPermissions($group->id, $initialPermissions);

    $resolvedGroup = UserGroups::getGroupById($group->id);
    $originalGroup = $resolvedGroup->getConfig();
    $originalConfig = $projectConfig->get();
    Event::fake([UserGroupSaving::class, UserGroupSaved::class, UserGroupPermissionsSaved::class]);

    $response = postJson(action([UserGroupsController::class, 'store']), [
        'id' => $group->id,
        'name' => 'Updated group',
        'handle' => 'updatedGroup',
        'description' => 'Updated description',
        'permissions' => $permissions,
    ]);

    if (! $allowed) {
        $response->assertStatus(423);
        expect($group->fresh()->getAttributes())->toBe($group->getAttributes());
        expect($resolvedGroup->getConfig())->toBe($originalGroup);
        expect($projectConfig->get())->toBe($originalConfig);
        Event::assertNothingDispatched();

        return;
    }

    $response->assertOk();
    expect($group->fresh()->only(['name', 'handle', 'description']))->toBe($team
        ? $group->only(['name', 'handle', 'description'])
        : ['name' => 'Updated group', 'handle' => 'updatedGroup', 'description' => 'Updated description']);

    $expectedPermissions = $team ? array_diff($permissions, ['accessCp']) : $permissions;
    expect(UserPermissions::getPermissionsByGroupId($group->id)->all())->toEqualCanonicalizing($expectedPermissions);
    expect($projectConfig->get(ProjectConfig::PATH_USER_GROUPS.'.'.$group->uid.'.permissions'))->toEqualCanonicalizing($expectedPermissions);
    Event::assertDispatchedOnce(UserGroupSaving::class);
    Event::assertDispatchedOnce(UserGroupSaved::class);
    Event::assertDispatchedOnce(UserGroupPermissionsSaved::class);

    if ($team) {
        expect(UserPermissions::doesUserHavePermission(Auth::id(), 'accessCp'))->toBeTrue();
    }
})->with([
    'expired addition' => [['accessCp'], ['accessCp', 'accessSiteWhenSystemIsOff'], false, false],
    'confirmed addition' => [['accessCp'], ['accessCp', 'accessSiteWhenSystemIsOff'], true, true],
    'metadata only' => [[], [], false, true],
    'permission removal' => [['accessCp', 'accessSiteWhenSystemIsOff'], ['accessCp'], false, true],
    'unchanged permissions' => [['accessCp'], ['accessCp'], false, true],
    'Team expired addition' => [[], ['accessSiteWhenSystemIsOff'], false, false, true],
    'Team confirmed addition' => [[], ['accessSiteWhenSystemIsOff'], true, true, true],
    'Team retains CP access' => [[], [], false, true, true],
]);
