<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\User\Users;
use Laravel\Passport\Passport;

it('preserves direct permission assignments without copying inherited grants during replacement', function (): void {
    Edition::set(Edition::Pro);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');

    $user = User::factory()->create();
    $group = UserGroup::factory()->create();
    app(UserPermissions::class)->saveGroupPermissions($group->id, ['accessCp', 'accessSiteWhenSystemIsOff']);
    app(Users::class)->assignUserToGroups($user->id, [$group->id]);
    app(UserPermissions::class)->saveUserPermissions($user->id, ['accessCp']);

    $call = fn (string $tool, array $arguments): array => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => ['userId' => $user->id, ...$arguments],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');

    $current = $call('user-permissions.user.get', []);

    expect($current['permissions'])->toContain('accessCp', 'accessSiteWhenSystemIsOff')
        ->and($current['directPermissions'])->toBe(['accessCp']);

    $updated = $call('user-permissions.user.set', ['permissions' => [...$current['directPermissions'], 'viewUsers']]);
    expect($updated['directPermissions'])->toEqualCanonicalizing(['accessCp', 'viewUsers']);

    app(Users::class)->assignUserToGroups($user->id, []);
    app()->forgetInstance(UserPermissions::class);
    $afterRemoval = $call('user-permissions.user.get', []);

    expect($afterRemoval['permissions'])->toContain('accessCp', 'viewUsers')
        ->not->toContain('accessSiteWhenSystemIsOff');

    app(Users::class)->assignUserToGroups($user->id, [$group->id]);
    $cleared = $call('user-permissions.user.set', ['permissions' => []]);
    expect($cleared['directPermissions'])->toBe([])
        ->and($cleared['permissions'])->toContain('accessCp', 'accessSiteWhenSystemIsOff')
        ->not->toContain('viewUsers');
});
