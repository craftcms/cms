<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\elements\Category;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\gql\ArgumentManager as LegacyArgumentManager;
use craft\services\Categories as LegacyCategories;
use craft\services\Elements as LegacyElements;
use craft\services\Globals as LegacyGlobals;
use craft\services\UserPermissions as LegacyUserPermissions;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Gql\GqlArguments;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Yii2Adapter\DeprecatedConcepts;
use Illuminate\Support\Collection;

afterEach(function() {
    DeprecatedConcepts::resetSupport();
    YiiEvent::off(LegacyElements::class, LegacyElements::EVENT_REGISTER_ELEMENT_TYPES);
    YiiEvent::off(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS);
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
});

it('finalizes registrations added by deprecated concepts after the early bridge setup', function() {
    new ReflectionProperty(DeprecatedConcepts::class, 'supportsCategories')->setValue(null, true);
    new ReflectionProperty(DeprecatedConcepts::class, 'supportsGlobalSets')->setValue(null, true);
    new ReflectionProperty(DeprecatedConcepts::class, 'supportsTags')->setValue(null, true);
    YiiEvent::off(LegacyElements::class, LegacyElements::EVENT_REGISTER_ELEMENT_TYPES);
    YiiEvent::off(LegacyArgumentManager::class, LegacyArgumentManager::EVENT_DEFINE_GQL_ARGUMENT_HANDLERS);
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
    DeprecatedConcepts::bootYiiEvents();

    $categories = Craft::$app->getCategories();
    $globals = Craft::$app->getGlobals();

    Craft::$app->set('categories', Mockery::mock(LegacyCategories::class, function($mock) {
        $mock->shouldReceive('getAllGroups')->andReturn([(object) [
            'name' => 'News',
            'uid' => 'news',
        ]]);
    }));
    Craft::$app->set('globals', Mockery::mock(LegacyGlobals::class, function($mock) {
        $mock->shouldReceive('getAllSets')->andReturn([(object) [
            'name' => 'Company',
            'uid' => 'company',
        ]]);
    }));

    app()->instance(Plugins::class, Mockery::mock(Plugins::class, function($mock) {
        $mock->shouldReceive('getAllPlugins')->andReturn([]);
    }));
    app()->instance(Utilities::class, Mockery::mock(Utilities::class, function($mock) {
        $mock->shouldReceive('getAllUtilityTypes')->andReturn(Collection::make());
    }));
    UserGroups::shouldReceive('getAllGroups')->andReturn(Collection::make());
    Sites::shouldReceive('isMultiSite')->andReturnFalse();
    Sections::shouldReceive('getAllSections')->andReturn(Collection::make());
    Volumes::shouldReceive('getAllVolumes')->andReturn(Collection::make());

    try {
        LegacyElements::finalizeRegistrationEvents();
        LegacyUserPermissions::finalizeRegistrationEvents();

        $permissionKeys = app(UserPermissions::class)->getAllPermissions()
            ->flatMap->permissions
            ->pluck('key');

        expect(app(ElementTypes::class)->types())
            ->toContain(Category::class, GlobalSet::class, Tag::class)
            ->and(app(GqlArguments::class)->handlers())
            ->toHaveKeys(['relatedToCategories', 'relatedToTags'])
            ->and($permissionKeys)
            ->toContain('viewCategories:news', 'editGlobalSet:company');
    } finally {
        Craft::$app->set('categories', $categories);
        Craft::$app->set('globals', $globals);
    }
});
