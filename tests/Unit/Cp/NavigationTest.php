<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Element\ElementSources;
use CraftCms\Cms\Entry\Entries;
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

    Auth::shouldReceive('user')->andReturn($user);
    Auth::shouldReceive('userResolver')->andReturn(fn () => $user);
});

it('selects nav items from paths with the cp trigger', function () {
    $request = Request::create('/admin/settings/fields');
    $navigation = new Navigation(
        $request,
        Mockery::mock(Plugins::class, ['getAllPlugins' => []]),
        Mockery::mock(Utilities::class, ['getAuthorizedUtilityTypes' => new Collection]),
        Cms::config(),
        Mockery::mock(ElementSources::class),
        Mockery::mock(Entries::class),
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
        Mockery::mock(Utilities::class, ['getAuthorizedUtilityTypes' => new Collection]),
        Cms::config(),
        Mockery::mock(ElementSources::class),
        Mockery::mock(Entries::class),
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
            ->shouldReceive('getItems')
            ->once()
            ->andReturn([
                ['label' => 'Dashboard'],
            ])),
    );

    expect((new Cp)->nav())->toBe([
        ['label' => 'Dashboard'],
    ]);
});
