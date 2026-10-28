<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Enums\Theme;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;

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
