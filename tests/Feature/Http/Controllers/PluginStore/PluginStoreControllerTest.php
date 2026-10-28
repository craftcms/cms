<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\currentUser;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires login', function () {
    auth()->logout();

    get(action([PluginStoreController::class, 'index']))
        ->assertRedirect(Cms::config()->cpTrigger.'/login');
});

it('renders the discover page with the install’s context', function () {
    get(action([PluginStoreController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('plugin-store/Index')
            ->where('context.edition', Edition::get()->handle())
            ->where('context.phpVersion', PHP::version())
            ->has('context.editions', count(Edition::cases())),
        );
});

it('routes plugin store pages to their screens', function (string $uri, string $component, array $props) {
    get(Cms::config()->cpTrigger.'/plugin-store/'.$uri)
        ->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($component, $props) {
            $page->component($component);

            foreach ($props as $key => $value) {
                $page->where($key, $value);
            }
        });
})->with([
    'category' => ['categories/12', 'plugin-store/Category', ['categoryId' => 12]],
    'featured' => ['featured/new-and-noteworthy', 'plugin-store/Featured', ['handle' => 'new-and-noteworthy']],
    'developer' => ['developer/7', 'plugin-store/Developer', ['developerId' => 7]],
    'search' => ['search?q=+seo+', 'plugin-store/Search', ['query' => 'seo']],
    'plugin' => ['seomatic', 'plugin-store/Plugin', ['handle' => 'seomatic', 'tab' => 'overview']],
    'plugin tab' => ['seomatic/changelog', 'plugin-store/Plugin', ['handle' => 'seomatic', 'tab' => 'changelog']],
]);

it('doesn’t take unknown plugin tabs', function () {
    get(Cms::config()->cpTrigger.'/plugin-store/seomatic/settings')->assertNotFound();
});

it('leaves buying and upgrading to the standalone app', function (string $uri) {
    get(Cms::config()->cpTrigger.'/plugin-store/'.$uri)
        ->assertOk()
        ->assertSee('legacy/pluginstore/dist/js/app.js')
        ->assertSee('legacy/axios/dist/axios.js');
})->with([
    'upgrade-craft',
    'buy/seomatic',
    'buy/seomatic/pro',
    'buy-all-trials',
]);

it('can return craft data', function () {
    getJson(action([PluginStoreController::class, 'craftData']))
        ->assertOk()
        ->assertJsonFragment(['currentUser' => currentUser()?->asElement()->email])
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
