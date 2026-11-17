<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Enums\Theme;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

it('resolves the theme the control panel is showing', function (?string $preference, ?string $cookie, Theme $expected) {
    $user = User::findOne();

    if ($preference !== null) {
        app(Users::class)->saveUserPreferences($user, ['theme' => $preference]);
    }

    if ($cookie !== null) {
        request()->cookies->set(Theme::COOKIE, $cookie);
    }

    expect(Theme::resolveFor($user))->toBe($expected);
})->with([
    'chosen dark' => ['dark', 'light', Theme::Dark],
    'chosen light' => ['light', 'dark', Theme::Light],
    'system, reported dark' => ['system', 'dark', Theme::Dark],
    'system, reported light' => ['system', 'light', Theme::Light],
    'system, not reported yet' => [null, null, Theme::Light],
]);

it('gives logged-out pages the last signed-in user’s preference', function (?string $cookie, Theme $expected) {
    if ($cookie !== null) {
        request()->cookies->set(Theme::PREFERENCE_COOKIE, $cookie);
    }

    expect(Theme::forUser(null))->toBe($expected);
})->with([
    'dark' => ['dark', Theme::Dark],
    'light' => ['light', Theme::Light],
    'system' => ['system', Theme::System],
    'invalid' => ['blue', Theme::System],
    'not set' => [null, Theme::System],
]);

it('ignores the last signed-in user’s preference when a user is logged in', function () {
    request()->cookies->set(Theme::PREFERENCE_COOKIE, 'dark');

    expect(Theme::forUser(User::findOne()))->toBe(Theme::System);
});

it('remembers the user’s preference when they sign in and out', function () {
    config()->set('session.path', '/app');
    config()->set('session.domain', '.example.test');
    $user = User::findOne();
    app(Users::class)->saveUserPreferences($user, ['theme' => 'dark']);

    Auth::login($user);

    expect(Cookie::queued(Theme::PREFERENCE_COOKIE)?->getValue())->toBe('dark');

    app(Users::class)->saveUserPreferences($user, ['theme' => 'light']);
    Cookie::flushQueuedCookies();
    Auth::logout();

    expect(Cookie::queued(Theme::PREFERENCE_COOKIE))
        ->getValue()->toBe('light')
        ->getPath()->toBe('/')
        ->getDomain()->toBeNull()
        ->isHttpOnly()->toBeFalse();
});
