<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\User\Users;
use Laravel\Passport\Passport;

it('preserves direct permission assignments without copying inherited grants during replacement', function (): void {
    Edition::set(Edition::Pro);
    $key = passportKey();
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');

    $user = User::factory()->create();
    $group = UserGroup::factory()->create();
    app(UserPermissions::class)->saveGroupPermissions($group->id, ['accessCp', 'accessSiteWhenSystemIsOff']);
    app(Users::class)->assignUserToGroups($user->id, [$group->id]);
    app(UserPermissions::class)->saveUserPermissions($user->id, ['accessCp']);

    $call = fn (string $tool, array $arguments): array => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => ['userId' => $user->id, ...$arguments],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');

    $current = $call('user_permissions_user_get', []);

    expect($current['permissions'])->toContain('accessCp', 'accessSiteWhenSystemIsOff')
        ->and($current['directPermissions'])->toBe(['accessCp']);

    $updated = $call('user_permissions_user_set', ['permissions' => [...$current['directPermissions'], 'viewUsers']]);
    expect($updated['directPermissions'])->toEqualCanonicalizing(['accessCp', 'viewUsers']);

    app(Users::class)->assignUserToGroups($user->id, []);
    app()->forgetInstance(UserPermissions::class);
    $afterRemoval = $call('user_permissions_user_get', []);

    expect($afterRemoval['permissions'])->toContain('accessCp', 'viewUsers')
        ->not->toContain('accessSiteWhenSystemIsOff');

    app(Users::class)->assignUserToGroups($user->id, [$group->id]);
    $cleared = $call('user_permissions_user_set', ['permissions' => []]);
    expect($cleared['directPermissions'])->toBe([])
        ->and($cleared['permissions'])->toContain('accessCp', 'accessSiteWhenSystemIsOff')
        ->not->toContain('viewUsers');
});

it('keeps stored group permissions whose provider is not registered', function (): void {
    Edition::set(Edition::Pro);
    $key = passportKey();
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');

    app(UserPermissions::class)->registerPermissionGroup('plugin:probe', fn () => new PermissionGroup(
        handle: 'plugin:probe',
        heading: 'Probe',
        permissions: collect([new Permission('probe:view', 'View', nested: collect([new Permission('probe:edit', 'Edit')]))]),
    ));
    $group = UserGroup::factory()->create();
    app(UserPermissions::class)->saveGroupPermissions($group->id, ['accessCp', 'probe:view']);
    $user = User::factory()->create();
    app(Users::class)->assignUserToGroups($user->id, [$group->id]);
    app(UserPermissions::class)->saveUserPermissions($user->id, ['probe:view', 'probe:edit']);

    // Like a plugin that only registers its permissions for control panel requests, over stdio
    app(UserPermissions::class)->removePermissionGroups('plugin:probe');

    $call = fn (string $tool, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => ['groupId' => $group->id, ...$arguments],
    ])->assertOk();

    $current = $call('user_permissions_group_get', [])->json('result.structuredContent.permissions');
    $call('user_permissions_group_set', ['permissions' => [...$current, 'accessSiteWhenSystemIsOff']])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.permissions', ['accessCp', 'accessSiteWhenSystemIsOff', 'probe:view']);
    $call('user_permissions_group_update', ['add' => ['probe:edit']])
        ->assertJsonPath('result.isError', true)
        ->assertSee('probe:edit');
    $call('user_permissions_group_update', ['remove' => ['accessCpp']])
        ->assertJsonPath('result.isError', true)
        ->assertSee('accessCpp');
    $call('user_permissions_group_update', ['add' => ['viewUsers'], 'remove' => ['accessSiteWhenSystemIsOff']])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.permissions', ['accessCp', 'probe:view', 'viewUsers']);
    $call('user_permissions_group_update', ['remove' => ['probe:view']])
        ->assertJsonPath('result.structuredContent.permissions', ['accessCp', 'viewUsers']);

    // Direct user permissions keep unregistered stored grants, without copying group-only ones
    $callUser = fn (string $tool, array $arguments) => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => ['userId' => $user->id, ...$arguments],
    ])->assertOk();

    app(UserPermissions::class)->saveGroupPermissions($group->id, ['probe:view']);
    expect($callUser('user_permissions_user_update', ['add' => ['accessCp']])
        ->assertJsonPath('result.isError', false)
        ->json('result.structuredContent.directPermissions'))
        ->toEqualCanonicalizing(['accessCp', 'probe:edit', 'probe:view']);
    $callUser('user_permissions_user_set', ['permissions' => ['accessCp', 'probe:edit', 'probe:other']])
        ->assertJsonPath('result.isError', true)
        ->assertSee('probe:other');
    expect($callUser('user_permissions_user_update', ['remove' => ['probe:view']])
        ->json('result.structuredContent.directPermissions'))
        ->toEqualCanonicalizing(['accessCp', 'probe:edit']);
});
