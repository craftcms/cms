<?php

declare(strict_types=1);

use JMac\Testing\Double;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Navigation;
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

    $user = Double::for(CraftUser::class);
    $user->allows('isAdmin')->returns(true);
    $user->allows('can')->returns(true);

    Auth::shouldReceive('user')->andReturn($user);
    Auth::shouldReceive('userResolver')->andReturn(fn () => $user);
});

it('selects nav items from paths with the cp trigger', function () {
    $request = Request::create('/admin/settings/fields');
    $plugins2 = Double::for(Plugins::class);
    $plugins2->allows('getAllPlugins')->returns([]);

    $utilities2 = Double::for(Utilities::class);
    $utilities2->allows('getAuthorizedUtilityTypes')->returns(new Collection);

    $navigation = new Navigation(
        $request,
        $plugins2,
        $utilities2,
        Cms::config(),
        Double::for(ElementSources::class),
    );

    $settingsItem = collect($navigation->getItems())->firstWhere('label', 'Settings');

    expect($settingsItem->selected)->toBeTrue()
        ->and($settingsItem->linkAttributes['aria']['current'])->toBe('true');
});

it('selects parent nav items when a subnav item matches the cp path', function () {
    $request = Request::create('/admin/graphql/tokens');
    $plugins = Double::for(Plugins::class);
    $plugins->allows('getAllPlugins')->returns([]);

    $utilities = Double::for(Utilities::class);
    $utilities->allows('getAuthorizedUtilityTypes')->returns(new Collection);

    $navigation = new Navigation(
        $request,
        $plugins,
        $utilities,
        Cms::config(),
        Double::for(ElementSources::class),
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
        Mockery::mock(Navigation::class, fn ($mock) => $mock->expects('getItems')->returns([
                ['label' => 'Dashboard'],
            ])),
    );

    expect((new Cp)->nav())->toBe([
        ['label' => 'Dashboard'],
    ]);
});
