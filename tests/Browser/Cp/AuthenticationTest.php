<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\Models\Authenticator;
use CraftCms\Cms\User\Models\User;
use PragmaRX\Google2FA\Google2FA;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;

it('signs in and continues to the requested page', function () {
    visitCp('settings/sections')
        ->type('role=textbox[name="Username or Email"]', 'craftcms')
        ->type('role=textbox[name="Password"]', 'craftcms2018!!')
        ->click('role=button[name="Sign in"]')
        ->assertPathEndsWith('/settings/sections')
        ->assertSeeIn('#main h1', 'Sections');

    assertAuthenticated();
});

it('rejects an incorrect password', function () {
    visitCp('login')
        ->type('role=textbox[name="Username or Email"]', 'craftcms')
        ->type('role=textbox[name="Password"]', 'not-the-password')
        ->click('role=button[name="Sign in"]')
        ->assertSee('Invalid username or password.')
        ->assertPathEndsWith('/login');

    assertGuest();
});

it('signs out from the user menu', function () {
    actingAs(User::first());

    visitCp('dashboard')
        ->click('role=button[name="User menu"]')
        ->click('role=button[name="Sign out"]')
        ->assertPathEndsWith('/login');

    visitCp('dashboard')->assertPathEndsWith('/login');
});

it('asks for an authenticator code after the password', function () {
    $secret = (new Google2FA)->generateSecretKey();
    Authenticator::create(['userId' => User::first()->id, 'auth2faSecret' => $secret]);

    visitCp('settings')
        ->type('role=textbox[name="Username or Email"]', 'craftcms')
        ->type('role=textbox[name="Password"]', 'craftcms2018!!')
        ->click('role=button[name="Sign in"]')
        ->type('role=textbox[name="Verification Code"]', (new Google2FA)->getCurrentOtp($secret))
        ->click('role=button[name="Verify"]')
        ->assertPathEndsWith('/settings');

    assertAuthenticated();
});
