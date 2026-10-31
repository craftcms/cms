<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController;
use CraftCms\Cms\Support\PHP;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::find()->one());
});

it('requires login', function () {
    auth()->logout();

    get(action([PluginStoreController::class, 'index']))
        ->assertRedirect(Cms::config()->cpTrigger.'/login');
});

it('renders the discover page with the install’s context and crumb', function () {
    get(action([PluginStoreController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('plugin-store/Index')
            ->where('context.edition', Edition::get()->handle())
            ->where('context.phpVersion', PHP::version())
            ->has('context.editions', count(Edition::cases()))
            ->where('crumbs.0.label', 'Plugin Store')
            ->where('crumbs.0.href', Url::cpUrl('plugin-store')),
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
    'buy' => ['buy/seomatic', 'plugin-store/Buy', ['handle' => 'seomatic', 'edition' => null]],
    'buy edition' => ['buy/seomatic/pro', 'plugin-store/Buy', ['handle' => 'seomatic', 'edition' => 'pro']],
    'buy all trials' => ['buy-all-trials', 'plugin-store/Buy', ['handle' => null, 'edition' => null]],
    'upgrade craft' => ['upgrade-craft', 'plugin-store/UpgradeCraft', ['title' => 'Upgrade Craft CMS']],
]);

it('doesn’t take unknown plugin tabs', function () {
    get(Cms::config()->cpTrigger.'/plugin-store/seomatic/settings')->assertNotFound();
});
