<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shares the CP language direction', function (string $language, string $orientation) {
    $user = User::find()->one();
    app(Users::class)->saveUserPreferences($user, ['language' => $language]);
    actingAs($user);

    get(action([GeneralSettingsController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('craft.orientation', $orientation))
        ->assertSee("dir=\"{$orientation}\"", escape: false)
        ->assertOk();
})->with([
    'left-to-right' => ['en-US', 'ltr'],
    'right-to-left' => ['ar', 'rtl'],
]);
