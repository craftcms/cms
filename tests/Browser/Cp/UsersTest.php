<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

it('creates a user and sets their permissions', function () {
    actingAs(User::first());

    visitCp('users/new')
        ->type('input[name="username"]', 'ada')
        ->type('input[name="email"]', 'ada@example.com')
        ->click('#main >> role=button[name="Create and set permissions"]')
        ->assertPathEndsWith('/permissions')
        ->click('#main >> role=checkbox[name="Access the control panel"]')
        ->click('#main >> role=checkbox[name="View users"]')
        ->click('#main >> role=button[name="Save"]')
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

    visit('/'.Cms::config()->cpTrigger.'/settings')->assertSee('403');
});
