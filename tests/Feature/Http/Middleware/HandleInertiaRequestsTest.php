<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
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

it('sends the nav tree again when a visit asks for it, although the client has it', function () {
    actingAs(User::find()->one());

    $navKey = 'craft.nav.'.(app(Navigation::class)->navSiteId() ?? 'all');
    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) Cp::vite()->manifestHash(),
        // The client already holds the tree.
        'X-Inertia-Except-Once-Props' => $navKey,
    ];
    $url = action([GeneralSettingsController::class, 'index']);

    $kept = get($url, $headers)->assertOk()->json('props.craft.nav');
    $refreshed = get($url, $headers + [HandleInertiaRequests::REFRESH_NAV_HEADER => '1'])
        ->assertOk()
        ->json('props.craft.nav');

    expect($kept)->toBeNull()
        ->and($refreshed)->toBeArray()->not->toBeEmpty();
});
