<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\MissingComponents;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPluginSettingsRequest;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function CraftCms\Cms\template;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    // Load test plugin
    loadTestPlugin();

    TestPlugin::$settingsRequestClass = Request::class;
});

test('requires authentication', function () {
    Auth::logout();

    get(action([PluginsController::class, 'index']))->assertRedirect();
    postJson(action([PluginsController::class, 'install'], ['test-plugin']))->assertUnauthorized();
    postJson(action([PluginsController::class, 'uninstall'], ['test-plugin']))->assertUnauthorized();
    postJson(action([PluginsController::class, 'enable'], ['test-plugin']))->assertUnauthorized();
    postJson(action([PluginsController::class, 'disable'], ['test-plugin']))->assertUnauthorized();
});

test('index shows plugin list page', function () {
    get(action([PluginsController::class, 'index']))
        ->assertOk()
        ->assertSee(t('Plugins'));
});

test('index shows read-only state when allowAdminChanges is false', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([PluginsController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Plugins')
            ->where('readOnly', true)
        );
});

test('install validates edition', function () {
    postJson(action([PluginsController::class, 'install'], ['test-plugin']), [
        'edition' => [],
    ])->assertJsonValidationErrors(['edition']);
});

test('install returns success message on successful installation', function () {
    postJson(action([PluginsController::class, 'install'], ['test-plugin']))->assertOk();
});

test('missing component actions target plugin CP routes', function (string $action, string $label) {
    $html = template('_special/missing-component', [
        'error' => 'Missing plugin',
        'iconUrl' => '/icon.svg',
        'iconSvg' => null,
        'pluginName' => 'Test Plugin',
        'action' => [
            'label' => $label,
            'url' => cp_url("settings/plugins/test-plugin/$action"),
            'method' => 'post',
        ],
    ], TemplateMode::Cp);

    expect($html)
        ->toContain('form="x"')
        ->toContain('formaction="'.cp_url("settings/plugins/test-plugin/$action").'"')
        ->not->toContain('data-action=');
})->with([
    'install' => ['install', 'Install'],
    'enable' => ['enable', 'Enable'],
]);

test('missing component resolution preserves legacy plugin store actions', function () {
    $presentation = app(MissingComponents::class)->resolve('craft\redactor\Field');

    expect($presentation)
        ->toMatchArray([
            'error' => 'Support for Redactor fields has been moved to a plugin.',
            'pluginName' => 'Redactor',
            'action' => [
                'label' => 'Install',
                'url' => cp_url('plugin-store/redactor'),
                'method' => 'get',
            ],
        ]);
});

test('missing component resolution hides plugin actions in read-only mode', function () {
    Cms::config()->allowAdminChanges = false;

    expect(app(MissingComponents::class)->resolve(TestPlugin::class))
        ->toMatchArray([
            'pluginName' => null,
            'action' => null,
        ]);
});

test('switchEdition validates edition field', function () {
    postJson(action([PluginsController::class, 'switchEdition'], ['test-plugin']))
        ->assertJsonValidationErrors(['edition']);
});

test('switchEdition changes the plugin edition', function () {
    postJson(action([PluginsController::class, 'switchEdition'], ['test-plugin']), [
        'edition' => 'pro',
    ])->assertOk();

    expect(app(Plugins::class)->getPlugin('test-plugin')->edition)->toBe('pro');
});

test('editSettings returns 404 for non-existent plugin', function () {
    get(action([PluginsController::class, 'editSettings'], ['non-existent-plugin']))
        ->assertNotFound();
});

test('editSettings returns 403 when allowAdminChanges is false and plugin lacks readonly support', function () {
    Cms::config()->allowAdminChanges = false;

    app(Plugins::class)->getPlugin('test-plugin')->hasReadOnlyCpSettings = false;

    get(action([PluginsController::class, 'editSettings'], ['test-plugin']))
        ->assertForbidden();
});

test('editSettings loads for existing plugin', function () {
    get(action([PluginsController::class, 'editSettings'], ['test-plugin']))
        ->assertOk()
        ->assertSee('Test Plugin')
        ->assertSee('settings-foo');
});

test('plugin settings form targets the plugin CP route', function () {
    $html = get(action([PluginsController::class, 'editSettings'], ['test-plugin']))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContainTag('form', [
            'id' => 'main-form',
            'action' => cp_url('settings/plugins/test-plugin'),
            'method' => 'post',
        ])
        ->not->toContainTag('input', [
            'name' => 'action',
            'value' => 'plugins/save-plugin-settings',
        ]);
});

test('editSettings returns read-only settings response when supported', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([PluginsController::class, 'editSettings'], ['test-plugin']))
        ->assertOk()
        ->assertSee('settings-foo')
        ->assertSee('disabled');
});

test('saveSettings validates settings', function () {
    postJson(action([PluginsController::class, 'saveSettings'], ['test-plugin']), [
        'settings' => 'invalid',
    ])->assertJsonValidationErrors(['settings']);
});

test('saveSettings returns 404 for non-existent plugin', function () {
    postJson(action([PluginsController::class, 'saveSettings'], ['non-existent-plugin']))
        ->assertNotFound();
});

test('saveSettings persists plugin settings', function () {
    postJson(action([PluginsController::class, 'saveSettings'], ['test-plugin']), [
        'settings' => ['foo' => 'bar', 'bar' => 'baz'],
    ])->assertOk();

    $settings = app(Plugins::class)->getPlugin('test-plugin')->getSettings();

    expect($settings->foo)->toBe('bar');
    expect($settings->bar)->toBe('baz');
});

test('saveSettings uses plugin form request validation', function () {
    TestPlugin::$settingsRequestClass = TestPluginSettingsRequest::class;

    postJson(action([PluginsController::class, 'saveSettings'], ['test-plugin']), [
        'settings' => ['foo' => 'invalid'],
    ])->assertJsonValidationErrors(['settings.foo']);

    expect(app(Plugins::class)->getPlugin('test-plugin')->getSettings()->foo)->toBeNull();

    postJson(action([PluginsController::class, 'saveSettings'], ['test-plugin']), [
        'settings' => ['foo' => 'via-form-request', 'bar' => 'raw-only'],
    ])->assertOk();

    $settings = app(Plugins::class)->getPlugin('test-plugin')->getSettings();

    expect($settings->foo)->toBe('via-form-request');
    expect($settings->bar)->toBeNull();
});

test('respects read-only mode for install', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'install'], ['test-plugin']))
        ->assertForbidden();
});

test('respects read-only mode for uninstall', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'uninstall'], ['test-plugin']))
        ->assertForbidden();
});

test('respects read-only mode for enable', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'enable'], ['test-plugin']))
        ->assertForbidden();
});

test('respects read-only mode for disable', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'disable'], ['test-plugin']))
        ->assertForbidden();
});

test('respects read-only mode for switchEdition', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'switchEdition'], ['test-plugin']), [
        'edition' => 'pro',
    ])
        ->assertForbidden();
});

test('respects read-only mode for saveSettings', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'saveSettings'], ['test-plugin']), [
        'settings' => [],
    ])
        ->assertForbidden();
});
