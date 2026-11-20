<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

it('creates a user and sets their permissions', function () {
    actingAs(User::first());

    $page = visitCp('users/new')
        ->type('input[name="username"]', 'ada')
        ->type('input[name="email"]', 'ada@example.com')
        ->click('#main >> role=button[name="Create and set permissions"]')
        ->assertPathEndsWith('/permissions');

    assertNoAccessibilityIssues($page
        ->click('#main >> role=checkbox[name="Access the control panel"]')
        ->click('#main >> role=checkbox[name="View users"]')
        ->click('#main >> role=button[name="Save"]')
        ->assertVisible('role=textbox[name="Password"]'))
        ->type('role=textbox[name="Password"]', 'craftcms2018!!')
        ->click('role=button[name="Sign in"]')
        ->assertSee('Permissions saved.');

    $user = User::query()->where('username', 'ada')->firstOrFail();

    expect($user->email)->toBe('ada@example.com')
        ->and(UserPermissions::getPermissionsByUserId($user->id)->sort()->values()->all())->toBe(['accessCp', 'viewUsers']);
});

it('keeps a user without admin rights out of the settings', function () {
    $user = User::factory()->withPermissions(['accessCp'])->create(['username' => 'editor']);

    visitCp('dashboard')
        ->type('role=textbox[name="Username or Email"]', 'editor')
        ->type('role=textbox[name="Password"]', 'password')
        ->click('role=button[name="Sign in"]')
        ->assertPathEndsWith('/dashboard')
        ->assertVisible('nav[aria-label="Primary"] >> role=link[name="Dashboard"]')
        ->assertMissing('nav[aria-label="Primary"] >> role=link[name="Settings"]')
        ->assertMissing('nav[aria-label="Primary"] >> role=link[name="Users"]');

    visit('/'.Cms::config()->routing->cpTrigger.'/settings')->assertSee('User is not permitted to perform this action.');
});

describe('with the workbench content', function () {
    beforeEach(function () {
        seedWorkbenchContent();
        actingAs(User::first());
    });

    it('renders the user pages', function (string $path, string $heading) {
        assertCpPageRenders($path, $heading);
    })->with([
        'users' => ['users', 'Users'],
        'new user' => ['users/new', 'New user'],
        'user' => [fn () => 'users/'.User::query()->where('username', 'ada')->value('id'), 'Ada Lovelace'],
        'user addresses' => [fn () => 'users/'.User::query()->where('username', 'ada')->value('id').'/addresses', 'Ada Lovelace'],
        'user permissions' => [fn () => 'users/'.User::query()->where('username', 'ada')->value('id').'/permissions', 'Ada Lovelace'],
    ]);
});
