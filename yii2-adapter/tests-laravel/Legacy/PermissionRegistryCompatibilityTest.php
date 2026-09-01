<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions as LegacyUserPermissions;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Support\Collection;

beforeEach(function() {
    app()->instance(Plugins::class, Mockery::mock(Plugins::class, function($mock) {
        $mock->shouldReceive('getAllPlugins')->andReturn([])->byDefault();
    }));
    app()->instance(Utilities::class, Mockery::mock(Utilities::class, function($mock) {
        $mock->shouldReceive('getAllUtilityTypes')->andReturn(Collection::make())->byDefault();
    }));
    UserGroups::shouldReceive('getAllGroups')->andReturn(Collection::make())->byDefault();
    Sites::shouldReceive('isMultiSite')->andReturnFalse()->byDefault();
    Sections::shouldReceive('getAllSections')->andReturn(Collection::make())->byDefault();
    Volumes::shouldReceive('getAllVolumes')->andReturn(Collection::make())->byDefault();
});

afterEach(function() {
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
});

it('accepts duplicate legacy headings and associative group keys', function() {
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
    $service = app(UserPermissions::class);
    YiiEvent::on(
        LegacyUserPermissions::class,
        LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS,
        function(RegisterUserPermissionsEvent $event) {
            $event->permissions[] = [
                'heading' => 'Shared heading',
                'permissions' => [],
            ];
            $event->permissions[] = [
                'heading' => 'Shared heading',
                'permissions' => [],
            ];
            $event->permissions['wheelform'] = [
                'heading' => 'Wheelform',
                'permissions' => [],
            ];
        },
    );

    LegacyUserPermissions::finalizeRegistrationEvents();

    $groups = $service->getAllPermissions();

    expect($groups->where('heading', 'Shared heading'))->toHaveCount(2)
        ->and($groups->pluck('handle')->unique())->toHaveCount($groups->count())
        ->and($groups->firstWhere('heading', 'Wheelform')->handle)->toBe('yii2-adapter:legacy:wheelform');
});

it('preserves stable permission group handles when legacy handlers change headings', function() {
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
    $service = app(UserPermissions::class);
    $service->registerPermissionGroup('plugin:example', fn() => new PermissionGroup('plugin:example', 'Original heading'));
    YiiEvent::on(
        LegacyUserPermissions::class,
        LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS,
        function(RegisterUserPermissionsEvent $event) {
            foreach ($event->permissions as &$group) {
                if (($group['handle'] ?? null) === 'plugin:example') {
                    $group['heading'] = 'Translated heading';
                }
            }
        },
    );

    LegacyUserPermissions::finalizeRegistrationEvents();

    $groups = $service->getAllPermissions();

    expect($groups->firstWhere('handle', 'plugin:example')->heading)->toBe('Translated heading');
});

it('finalizes the legacy permission transformer idempotently', function() {
    $calls = 0;
    YiiEvent::on(
        LegacyUserPermissions::class,
        LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS,
        function() use (&$calls) {
            $calls++;
        },
    );

    LegacyUserPermissions::finalizeRegistrationEvents();
    LegacyUserPermissions::finalizeRegistrationEvents();
    app(UserPermissions::class)->getAllPermissions();

    expect($calls)->toBe(1);
});

it('rebuilds legacy permissions from current resources and locale', function() {
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
    $resource = 'old';
    $originalLocale = app()->getLocale();

    app()->instance(Plugins::class, Mockery::mock(Plugins::class, function($mock) {
        $mock->shouldReceive('getAllPlugins')->andReturn([]);
    }));
    app()->instance(Utilities::class, Mockery::mock(Utilities::class, function($mock) {
        $mock->shouldReceive('getAllUtilityTypes')->andReturn(Collection::make());
    }));
    UserGroups::shouldReceive('getAllGroups')->andReturn(Collection::make());
    Sites::shouldReceive('isMultiSite')->andReturnTrue();
    Sites::shouldReceive('getAllSites')->with(true)->andReturnUsing(function() use (&$resource) {
        $site = new Site();
        $site->name = ucfirst($resource);
        $site->uid = "$resource-site";

        return collect([$site]);
    });
    Sections::shouldReceive('getAllSections')->andReturnUsing(function() use (&$resource) {
        return collect([new Section([
            'name' => ucfirst($resource),
            'type' => SectionType::Channel,
            'uid' => "$resource-section",
        ])]);
    });
    Volumes::shouldReceive('getAllVolumes')->andReturnUsing(function() use (&$resource) {
        return collect([new Volume([
            'name' => ucfirst($resource),
            'uid' => "$resource-volume",
        ])]);
    });

    $eventLocales = [];
    YiiEvent::on(
        LegacyUserPermissions::class,
        LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS,
        function() use (&$eventLocales) {
            $eventLocales[] = app()->getLocale();
        },
    );

    try {
        LegacyUserPermissions::finalizeRegistrationEvents();

        $service = app(UserPermissions::class);
        $first = $service->getAllPermissions();

        expect($first->flatMap(fn(PermissionGroup $group) => $group->keys))
            ->toContain('editSite:old-site', 'viewEntries:old-section', 'viewAssets:old-volume');

        $resource = 'reset';
        $service->reset();
        $afterReset = $service->getAllPermissions();

        expect($afterReset->flatMap(fn(PermissionGroup $group) => $group->keys))
            ->toContain('editSite:reset-site', 'viewEntries:reset-section', 'viewAssets:reset-volume')
            ->not()->toContain('editSite:old-site', 'viewEntries:old-section', 'viewAssets:old-volume');

        $resource = 'scope';
        app()->setLocale('fr');
        app()->forgetScopedInstances();
        $nextScope = app(UserPermissions::class)->getAllPermissions();

        expect($nextScope->flatMap(fn(PermissionGroup $group) => $group->keys))
            ->toContain('editSite:scope-site', 'viewEntries:scope-section', 'viewAssets:scope-volume')
            ->not()->toContain('editSite:reset-site', 'viewEntries:reset-section', 'viewAssets:reset-volume')
            ->and(collect($eventLocales)->last())->toBe('fr');
    } finally {
        app()->setLocale($originalLocale);
    }
});
