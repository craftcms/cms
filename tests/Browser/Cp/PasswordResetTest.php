<?php

declare(strict_types=1);

use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('resets a forgotten password', function () {
    Notification::fake();
    $user = User::factory()->withPermissions(['accessCp'])->create(['username' => 'forgetful', 'email' => 'forgetful@example.com']);

    visitCp('login')
        ->click('role=button[name="Forgot password?"]')
        ->type('role=textbox[name="Username or Email"]', 'forgetful')
        ->click('role=button[name="Reset password"]')
        ->assertSee('Check your email for instructions to reset your password.');

    $link = null;
    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user, &$link) {
        $link = (string) $notification->toMail($user)->variables['link'];

        return true;
    });

    visit(parse_url($link, PHP_URL_PATH).'?'.parse_url($link, PHP_URL_QUERY))
        ->type('role=textbox[name="Choose a new password"]', 'a-fresh-start-2026')
        ->click('role=button[name="Set Password"]')
        ->assertPathIs('/');

    expect(Hash::check('a-fresh-start-2026', $user->fresh()->password))->toBeTrue();

    visitCp('login')
        ->type('role=textbox[name="Username or Email"]', 'forgetful')
        ->type('role=textbox[name="Password"]', 'a-fresh-start-2026')
        ->click('role=button[name="Sign in"]')
        ->assertPathEndsWith('/dashboard');
});
