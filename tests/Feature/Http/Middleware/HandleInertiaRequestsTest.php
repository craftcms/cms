<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Events\CpNavItemsResolving;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Http\Controllers\Settings\GeneralSettingsController;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Event;
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

it('sends the nav tree even when the client already has it', function () {
    Event::forget(CpNavItemsResolving::class);
    actingAs(User::find()->one());

    $navKey = 'craft.nav.'.(app(Navigation::class)->navSiteId() ?? 'all');
    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) Cp::vite()->manifestHash(),
        // The client already holds the tree.
        'X-Inertia-Except-Once-Props' => $navKey,
    ];
    $url = action([GeneralSettingsController::class, 'index']);

    $nav = get($url, $headers)->assertOk()->json('props.craft.nav');

    expect($nav)->toBeArray()->not->toBeEmpty();
});

it('updates dynamic navigation even when the client already has the tree', function (string $customization) {
    Event::forget(CpNavItemsResolving::class);
    actingAs(User::find()->one());

    $plugin = new class(app()) extends Plugin
    {
        public bool $showQueue = false;

        public function getCpNavItem(): ?NavItem
        {
            return $this->showQueue ? new NavItem()->label('Review queue')->href('review-queue') : null;
        }
    };
    $plugin->handle = 'review-queue';
    $plugin->hasCpSection = true;

    if ($customization === 'plugin') {
        $plugins = Mockery::mock(app(Plugins::class));
        $plugins->shouldReceive('getAllPlugins')->andReturn([$plugin]);
        app()->instance(Plugins::class, $plugins);
    } else {
        Event::listen(
            $customization === 'wildcard listener' ? 'CraftCms\\Cms\\Cp\\Events\\*' : CpNavItemsResolving::class,
            function (mixed ...$arguments) use ($plugin): void {
                $event = $arguments[0] instanceof CpNavItemsResolving ? $arguments[0] : $arguments[1][0];

                if ($event instanceof CpNavItemsResolving && $plugin->showQueue) {
                    $event->navItems[] = $plugin->getCpNavItem();
                }
            },
        );
    }

    $url = action([GeneralSettingsController::class, 'index']);
    $headers = [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) Cp::vite()->manifestHash(),
    ];

    $navLabels = fn (array $items): array => collect($items)
        ->flatMap(fn (array $item): array => $item['group'] ? $item['subnav'] : [$item])
        ->pluck('label')
        ->all();

    $initial = get($url, $headers)->assertOk()->json('props.craft.nav');
    expect($navLabels($initial))->not->toContain('Review queue');

    $headers['X-Inertia-Except-Once-Props'] = 'craft.nav.'.(app(Navigation::class)->navSiteId() ?? 'all');
    $plugin->showQueue = true;
    $added = get($url, $headers)->assertOk()->json('props.craft.nav');
    expect($added)->toBeArray();
    expect($navLabels($added))->toContain('Review queue');

    $plugin->showQueue = false;
    $removed = get($url, $headers)->assertOk()->json('props.craft.nav');
    expect($removed)->toBeArray();
    expect($navLabels($removed))->not->toContain('Review queue');
})->with(['listener', 'wildcard listener', 'plugin']);
