<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->firstOrFail());
});

it('requires login', function () {
    auth()->logout();

    get(action([PluginStoreController::class, 'index']))
        ->assertRedirect(Cms::config()->cpTrigger.'/login');
});

it('renders the plugin store', function () {
    actingAs(User::find()->firstOrFail());

    get(action([PluginStoreController::class, 'index']))
        ->assertOk()
        ->assertSee(PHP::version());
});

it('can return craft data', function () {
    getJson(action([PluginStoreController::class, 'craftData']))
        ->assertOk()
        ->assertJsonFragment(['currentUser' => auth()->user()->email])
        ->assertJsonFragment(['CraftSolo' => Edition::Solo->value])
        ->assertJsonFragment(['CraftTeam' => Edition::Team->value])
        ->assertJsonFragment(['CraftPro' => Edition::Pro->value])
        ->assertJsonFragment(['CraftEnterprise' => Edition::Enterprise->value]);
});

it('can save plugin license keys', function () {
    loadTestPlugin();

    expect(app(Plugins::class)->getPluginLicenseKey('test-plugin'))->not()->toBe('foobar');

    postJson(action([PluginStoreController::class, 'savePluginLicenseKeys']), [
        'pluginLicenseKeys' => [
            [
                'handle' => 'test-plugin',
                'key' => $key = Str::random(24),
            ],
        ],
    ])->assertOk();

    expect(app(Plugins::class)->getPluginLicenseKey('test-plugin'))->toBe(strtoupper($key));
});
