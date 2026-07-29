<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterCpSettingsEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\SystemMessages as LegacySystemMessages;
use craft\services\UserPermissions as LegacyUserPermissions;
use craft\utilities\ClearCaches as LegacyClearCaches;
use craft\web\twig\variables\Cp as LegacyCp;
use craft\web\View as LegacyView;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Cp\Settings;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\NativeFields;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\Utility\Utilities;
use CraftCms\Cms\Utility\Utilities\ClearCaches;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use CraftCms\Yii2Adapter\Event\EventCompatibility;
use Illuminate\Support\Collection;

afterEach(function() {
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
    YiiEvent::off(LegacyView::class, LegacyView::EVENT_REGISTER_CP_TEMPLATE_ROOTS);
    YiiEvent::off(LegacyView::class, LegacyView::EVENT_REGISTER_SITE_TEMPLATE_ROOTS);
    YiiEvent::off(LegacySystemMessages::class, LegacySystemMessages::EVENT_REGISTER_MESSAGES);
    YiiEvent::off(LegacyCp::class, LegacyCp::EVENT_REGISTER_CP_SETTINGS);
    YiiEvent::off(LegacyCp::class, LegacyCp::EVENT_REGISTER_READ_ONLY_CP_SETTINGS);
    YiiEvent::off(LegacyClearCaches::class, LegacyClearCaches::EVENT_REGISTER_CACHE_OPTIONS);
    YiiEvent::off(LegacyClearCaches::class, LegacyClearCaches::EVENT_REGISTER_TAG_OPTIONS);
    YiiEvent::off(craft\models\FieldLayout::class, craft\models\FieldLayout::EVENT_DEFINE_NATIVE_FIELDS);
    app(Settings::class)->remove('Modules', 'modern');
    ClearCaches::flushState();
});

it('exposes native fields after repeated legacy event registration', function() {
    $registry = new NativeFields(app());
    app()->instance(NativeFields::class, $registry);

    YiiEvent::on(
        craft\models\FieldLayout::class,
        craft\models\FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
        function(DefineFieldLayoutFieldsEvent $event) {
            $event->fields = [EntryTitleField::class];
        },
    );

    craft\models\FieldLayout::registerEvents();
    craft\models\FieldLayout::registerEvents();

    expect($registry->apply(new FieldLayout()))->toBe([EntryTitleField::class]);
});

it('exposes keyed cache options to legacy reducers', function() {
    ClearCaches::add('modern', [
        'label' => 'Modern',
        'action' => static fn() => null,
    ]);
    LegacyClearCaches::registerEvents();
    $modernOptionWasVisible = false;

    YiiEvent::on(
        LegacyClearCaches::class,
        LegacyClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
        function(RegisterCacheOptionsEvent $event) use (&$modernOptionWasVisible) {
            $modernOptionWasVisible = collect($event->options)->contains('key', 'modern');
            $event->options = [[
                'key' => 'legacy',
                'label' => 'Legacy',
                'action' => static fn() => null,
            ]];
        },
    );

    $options = ClearCaches::cacheOptions();

    expect($options)->toHaveCount(1)
        ->and($options[0]['key'])->toBe('legacy')
        ->and($modernOptionWasVisible)->toBeTrue();
});

it('exposes cache tags to legacy reducers', function() {
    ClearCaches::addTag('modern', 'Modern');
    LegacyClearCaches::registerEvents();

    YiiEvent::on(
        LegacyClearCaches::class,
        LegacyClearCaches::EVENT_REGISTER_TAG_OPTIONS,
        function(RegisterCacheOptionsEvent $event) {
            expect($event->options)->toContain(['tag' => 'modern', 'label' => 'Modern']);
            $event->options = [['tag' => 'legacy', 'label' => 'Legacy']];
        },
    );

    expect(ClearCaches::tagOptions())->toBe([['tag' => 'legacy', 'label' => 'Legacy']]);
});

it('exposes keyed settings to legacy reducers', function() {
    app(Settings::class)->registerSetting('Modules', 'modern', fn() => ['label' => 'Modern']);
    $modernSettingWasVisible = false;

    YiiEvent::on(
        LegacyCp::class,
        LegacyCp::EVENT_REGISTER_CP_SETTINGS,
        function(RegisterCpSettingsEvent $event) use (&$modernSettingWasVisible) {
            $modernSettingWasVisible = $event->settings['Modules']['modern'] === ['label' => 'Modern'];
            $event->settings = ['Legacy' => ['replacement' => ['label' => 'Replacement']]];
        },
    );

    expect(app(Settings::class)->all())->toBe([
        'Legacy' => ['replacement' => ['label' => 'Replacement']],
    ])->and($modernSettingWasVisible)->toBeTrue();
});

it('uses the legacy read-only settings event', function() {
    $config = app(GeneralConfig::class);
    $allowAdminChanges = $config->allowAdminChanges;
    $config->allowAdminChanges = false;
    app(Settings::class)->registerReadOnlySetting('Modules', 'readonly', fn() => ['label' => 'Readonly']);

    YiiEvent::on(
        LegacyCp::class,
        LegacyCp::EVENT_REGISTER_READ_ONLY_CP_SETTINGS,
        function(RegisterCpSettingsEvent $event) {
            $event->settings = ['Legacy' => ['readonly' => ['label' => 'Readonly replacement']]];
        },
    );

    try {
        expect(app(Settings::class)->all())->toBe([
            'Legacy' => ['readonly' => ['label' => 'Readonly replacement']],
        ]);
    } finally {
        $config->allowAdminChanges = $allowAdminChanges;
        app(Settings::class)->remove('Modules', 'readonly');
    }
});

it('does not resolve system messages while finalizing startup registrations', function() {
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
    $factoryCalls = 0;
    app(SystemMessages::class)->register('modern', function() use (&$factoryCalls) {
        $factoryCalls++;

        return new SystemMessage([
            'key' => 'modern',
            'heading' => 'Modern message',
            'subject' => 'Modern message',
            'body' => 'Modern message',
        ]);
    });

    YiiEvent::on(
        LegacySystemMessages::class,
        LegacySystemMessages::EVENT_REGISTER_MESSAGES,
        fn() => null,
    );

    new EventCompatibility()->finalizeRegistrationEvents();

    expect($factoryCalls)->toBe(0);
});

it('does not resolve permissions without legacy event handlers', function() {
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
    app()->instance(UserPermissions::class, Mockery::mock(UserPermissions::class, function($mock) {
        $mock->shouldNotReceive('getAllPermissions');
    }));

    LegacyUserPermissions::finalizeRegistrationEvents();

    expect(app(UserPermissions::class))->toBeInstanceOf(UserPermissions::class);
});

it('exposes registered system messages to the legacy replacement event', function() {
    I18N::shouldReceive('getSiteLocaleIds')->andReturn(collect([app()->getLocale()]));
    I18N::shouldReceive('translate')->andReturnUsing(fn(string $message) => $message);

    $registry = app(SystemMessages::class);
    $registry->register('modern', fn() => new SystemMessage([
        'key' => 'modern',
        'heading' => 'Modern message',
        'subject' => 'Modern message',
        'body' => 'Modern message',
    ]));

    $modernMessageWasVisible = false;

    YiiEvent::on(
        LegacySystemMessages::class,
        LegacySystemMessages::EVENT_REGISTER_MESSAGES,
        function(RegisterEmailMessagesEvent $event) use (&$modernMessageWasVisible) {
            $modernMessageWasVisible = collect($event->messages)->contains(
                fn(array $message) => $message['key'] === 'modern',
            );
            $event->messages = [[
                'key' => 'legacy',
                'heading' => 'Legacy message',
                'subject' => 'Legacy message',
                'body' => 'Legacy message',
            ]];
        },
    );

    $messages = $registry->messages();

    expect($messages->keys()->all())->toBe(['legacy'])
        ->and($messages['legacy']->subject)->toBe('Legacy message')
        ->and($modernMessageWasVisible)->toBeTrue();
});

it('preserves the missing primary site failure for direct system message reads', function() {
    I18N::shouldReceive('getSiteLocaleIds')->andReturn(Collection::make());
    Sites::shouldReceive('getPrimarySite')->andThrow(new SiteNotFoundException('No primary site exists'));

    expect(fn() => app(SystemMessages::class)->messages())
        ->toThrow(SiteNotFoundException::class, 'No primary site exists');
});

it('exposes registered permission groups to the legacy resolving event', function() {
    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);

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

    $service = app(UserPermissions::class);
    $service->registerPermissionGroup(
        'plugin:modern',
        fn() => new PermissionGroup(
            handle: 'plugin:modern',
            heading: 'Modern plugin',
            permissions: collect([new Permission('manageModernPlugin', 'Manage modern plugin')]),
        ),
    );

    $modernGroupWasVisible = false;
    $eventCalls = 0;

    YiiEvent::on(
        LegacyUserPermissions::class,
        LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS,
        function(RegisterUserPermissionsEvent $event) use (&$eventCalls, &$modernGroupWasVisible) {
            $eventCalls++;
            $modernGroupWasVisible = collect($event->permissions)->contains(
                fn(array $group) => $group['heading'] === 'Modern plugin',
            );
            $event->permissions = [[
                'heading' => 'Legacy replacement',
                'permissions' => [],
            ]];
        },
    );

    LegacyUserPermissions::finalizeRegistrationEvents();

    expect($service->getAllPermissions()->pluck('heading')->all())->toBe(['Legacy replacement'])
        ->and($modernGroupWasVisible)->toBeTrue()
        ->and($eventCalls)->toBe(1);

    YiiEvent::off(LegacyUserPermissions::class, LegacyUserPermissions::EVENT_REGISTER_PERMISSIONS);
    $service->reset();

    expect($service->getAllPermissions()->pluck('heading'))
        ->toContain('Modern plugin')
        ->not()->toContain('Legacy replacement');
});

it('adds legacy template roots without replacing modern roots', function(
    TemplateMode $mode,
    string $legacyEvent,
) {
    YiiEvent::off(LegacyView::class, $legacyEvent);

    app(TemplateRoots::class)->register($mode, 'modern', '/modern');

    YiiEvent::on(LegacyView::class, $legacyEvent, function(RegisterTemplateRootsEvent $event) {
        $event->roots = ['legacy' => '/legacy'];
    });

    LegacyView::finalizeRegistrationEvents();

    $resolved = $mode->templateRoots();

    expect($resolved)->toHaveKeys(['legacy', 'modern']);

    YiiEvent::off(LegacyView::class, $legacyEvent);

    expect($mode->templateRoots())->toHaveKeys(['legacy', 'modern']);
})->with([
    'control panel' => [TemplateMode::Cp, LegacyView::EVENT_REGISTER_CP_TEMPLATE_ROOTS],
    'site' => [TemplateMode::Site, LegacyView::EVENT_REGISTER_SITE_TEMPLATE_ROOTS],
]);
