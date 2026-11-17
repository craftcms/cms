<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Models\Widget;
use CraftCms\Cms\Dashboard\Widgets\NewUsers;
use CraftCms\Cms\Edition;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('only shows New Users on editions that support it', function (Edition $edition, bool $available) {
    Edition::set($edition);
    actingAs($user = User::find()->one());
    UserModel::query()->whereKey($user->id)->update(['hasDashboard' => true]);
    Widget::query()->create([
        'userId' => $user->id,
        'type' => NewUsers::class,
        'settings' => [],
        'sortOrder' => 1,
    ]);

    get(route('craft.cp.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('widgets', $available ? 1 : 0)
            ->where('widgetTypes', fn ($types) => $types[NewUsers::class]['selectable'] === $available));
})->with([
    'Solo' => [Edition::Solo, false],
    'Team' => [Edition::Team, false],
    'Pro' => [Edition::Pro, true],
    'Enterprise' => [Edition::Enterprise, true],
]);

it('only shows New Users to users who can view users', function () {
    Edition::set(Edition::Pro);
    $user = UserModel::factory()->active()->withPermissions(['accessCp'])->create()->asElement();
    actingAs($user);
    UserModel::query()->whereKey($user->id)->update(['hasDashboard' => true]);
    Widget::query()->create([
        'userId' => $user->id,
        'type' => NewUsers::class,
        'settings' => [],
        'sortOrder' => 1,
    ]);

    get(route('craft.cp.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('widgets', 0)
            ->where('widgetTypes', fn ($types) => $types[NewUsers::class]['selectable'] === false));
});

it('names its user group for its total, singularized for a single user', function () {
    $group = UserGroup::factory()->create(['name' => 'Members']);

    expect(new NewUsers(['userGroupId' => $group->id])->props())
        ->toMatchArray(['userGroupName' => 'members', 'userGroupNameSingular' => 'member'])
        ->and((new NewUsers)->props())
        ->toMatchArray(['userGroupName' => null, 'userGroupNameSingular' => null]);
});
