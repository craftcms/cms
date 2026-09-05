<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Cp\Settings;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\Twig\Variables\Cp;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    Cms::setIsInstalled();

    Cms::config()
        ->cpTrigger('admin')
        ->enableGql(true)
        ->allowAdminChanges(true);

    Sections::shouldReceive('getTotalEditableSections')->andReturn(0);
    Volumes::shouldReceive('getTotalViewableVolumes')->andReturn(0);

    $user = Mockery::mock(CraftUser::class);
    $user->shouldReceive('isAdmin')->andReturnTrue();
    $user->shouldReceive('can')->andReturnTrue();
    // The nav caches per user, so building one asks who's asking.
    $user->shouldReceive('getCraftUserId')->andReturn(1);

    Auth::shouldReceive('user')->andReturn($user);
    Auth::shouldReceive('userResolver')->andReturn(fn () => $user);
});

it('selects nav items from paths with the cp trigger', function () {
    $request = Request::create('/admin/settings/fields');
    $navigation = new Navigation(
        $request,
        Mockery::mock(Plugins::class, ['getAllPlugins' => []]),
        Mockery::mock(Utilities::class, [
            'getAuthorizedUtilityTypes' => new Collection,
            'getUtilitiesBadgeCount' => 0,
        ]),
        Cms::config(),
        Mockery::mock(ElementSources::class),
        app(Settings::class),
    );

    $settingsItem = collect($navigation->getItems())->firstWhere('label', 'Settings');

    expect($settingsItem->selected)->toBeTrue()
        ->and($settingsItem->linkAttributes['aria']['current'])->toBe('true');
});

it('selects parent nav items when a subnav item matches the cp path', function () {
    $request = Request::create('/admin/graphql/tokens');
    $navigation = new Navigation(
        $request,
        Mockery::mock(Plugins::class, ['getAllPlugins' => []]),
        Mockery::mock(Utilities::class, [
            'getAuthorizedUtilityTypes' => new Collection,
            'getUtilitiesBadgeCount' => 0,
        ]),
        Cms::config(),
        Mockery::mock(ElementSources::class),
        app(Settings::class),
    );

    $graphqlItem = collect($navigation->getItems())->firstWhere('label', 'GraphQL');
    $tokensItem = collect($graphqlItem->subnav)->firstWhere('label', 'Tokens');

    expect($graphqlItem->selected)->toBeTrue()
        ->and($graphqlItem->linkAttributes['aria']['current'])->toBe('true')
        ->and($tokensItem->selected)->toBeTrue()
        ->and($tokensItem->linkAttributes['aria']['current'])->toBe('page');
});

it('uses the cp navigation service for the twig variable', function () {
    app()->instance(
        Navigation::class,
        Mockery::mock(Navigation::class, fn ($mock) => $mock
            ->shouldReceive('getShallowItems')
            ->once()
            ->andReturn([
                ['label' => 'Dashboard'],
            ])),
    );

    expect((new Cp)->nav())->toBe([
        ['label' => 'Dashboard'],
    ]);
});

/**
 * Builds a navigation whose Utilities service counts how often it's asked for
 * the authorized types — the expensive part of building a tree, and the thing
 * the cache is there to stop repeating.
 */
function navigationCountingBuilds(Request $request, callable $onBuild): Navigation
{
    $utilities = Mockery::mock(Utilities::class);
    $utilities->shouldReceive('getAuthorizedUtilityTypes')
        ->andReturnUsing(function () use ($onBuild) {
            $onBuild();

            return new Collection;
        });
    $utilities->shouldReceive('getUtilitiesBadgeCount')->andReturn(0);

    return new Navigation(
        $request,
        Mockery::mock(Plugins::class, ['getAllPlugins' => []]),
        $utilities,
        Cms::config(),
        Mockery::mock(ElementSources::class),
        app(Settings::class),
    );
}

it('builds the tree once and serves it from the cache after that', function () {
    $builds = 0;
    $navigation = navigationCountingBuilds(
        Request::create('/admin/settings/fields'),
        function () use (&$builds) {
            $builds++;
        },
    );

    $navigation->getTree();
    $navigation->getTree();
    $navigation->getTree();

    expect($builds)->toBe(1);
});

it('rebuilds after the cache is flushed', function () {
    $builds = 0;
    $navigation = navigationCountingBuilds(
        Request::create('/admin/settings/fields'),
        function () use (&$builds) {
            $builds++;
        },
    );

    $navigation->getTree();
    Navigation::flushCache();
    $navigation->getTree();

    expect($builds)->toBe(2);
});

it('keeps selection out of the cached tree', function () {
    $settings = new Navigation(
        Request::create('/admin/settings/fields'),
        Mockery::mock(Plugins::class, ['getAllPlugins' => []]),
        Mockery::mock(Utilities::class, [
            'getAuthorizedUtilityTypes' => new Collection,
            'getUtilitiesBadgeCount' => 0,
        ]),
        Cms::config(),
        Mockery::mock(ElementSources::class),
        app(Settings::class),
    );

    // Same user, same everything — so the second request reads the first
    // request's cached tree. If selection were baked into it, the nav would
    // still be pointing at Settings here.
    $settings->getItems();

    $graphql = new Navigation(
        Request::create('/admin/graphql/tokens'),
        Mockery::mock(Plugins::class, ['getAllPlugins' => []]),
        Mockery::mock(Utilities::class, [
            'getAuthorizedUtilityTypes' => new Collection,
            'getUtilitiesBadgeCount' => 0,
        ]),
        Cms::config(),
        Mockery::mock(ElementSources::class),
        app(Settings::class),
    );

    $items = collect($graphql->getItems());

    expect($items->firstWhere('label', 'GraphQL')->selected)->toBeTrue()
        ->and($items->firstWhere('label', 'Settings')->selected)->toBeFalse();
});

it('nests the settings screens under Settings, grouped as the index groups them', function () {
    $navigation = new Navigation(
        Request::create('/admin/settings'),
        Mockery::mock(Plugins::class, ['getAllPlugins' => []]),
        Mockery::mock(Utilities::class, [
            'getAuthorizedUtilityTypes' => new Collection,
            'getUtilitiesBadgeCount' => 0,
        ]),
        Cms::config(),
        Mockery::mock(ElementSources::class),
        app(Settings::class),
    );

    $settings = collect($navigation->getTree())->firstWhere('label', 'Settings');
    $groups = collect($settings->subnav);
    $system = $groups->firstWhere('label', 'System');

    // A section is a heading, not a destination.
    expect($groups)->not->toBeEmpty()
        ->and($system->group)->toBeTrue()
        ->and($system->href)->toBeNull()
        ->and(collect($system->subnav)->pluck('label'))->toContain('General');
});

it('hands a group\'s children up for the legacy sidebar, which has no groups', function () {
    $navigation = new Navigation(
        Request::create('/admin/settings'),
        Mockery::mock(Plugins::class, ['getAllPlugins' => []]),
        Mockery::mock(Utilities::class, [
            'getAuthorizedUtilityTypes' => new Collection,
            'getUtilitiesBadgeCount' => 0,
        ]),
        Cms::config(),
        Mockery::mock(ElementSources::class),
        app(Settings::class),
    );

    $settings = collect($navigation->getShallowItems())->firstWhere('label', 'Settings');
    $children = collect($settings->subnav);

    // Flat, and every one of them somewhere to go — a heading with no URL
    // would render as an unclickable nav item in the Twig sidebar.
    expect($children->pluck('label'))->toContain('General')
        ->and($children->every(fn ($item) => ! $item->group))->toBeTrue()
        ->and($children->every(fn ($item) => $item->href !== null))->toBeTrue();
});
