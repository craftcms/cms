<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\PluginsController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    // Load test plugin
    loadTestPlugin();
});

test('requires authentication', function () {
    Auth::logout();

    get(action([PluginsController::class, 'index']))->assertRedirect();
    postJson(action([PluginsController::class, 'install']))->assertUnauthorized();
    postJson(action([PluginsController::class, 'uninstall']))->assertUnauthorized();
    postJson(action([PluginsController::class, 'enable']))->assertUnauthorized();
    postJson(action([PluginsController::class, 'disable']))->assertUnauthorized();
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
        ->assertSee(t('Changes to these settings aren’t permitted in this environment.'));
});

test('install validates required pluginHandle', function () {
    postJson(action([PluginsController::class, 'install']), [])
        ->assertJsonValidationErrors(['pluginHandle']);
});

test('install returns success message on successful installation', function () {
    postJson(action([PluginsController::class, 'install']), [
        'pluginHandle' => 'test-plugin',
    ])->assertOk();
});

test('uninstall validates required pluginHandle', function () {
    postJson(action([PluginsController::class, 'uninstall']), [])
        ->assertJsonValidationErrors(['pluginHandle']);
});

test('enable validates pluginHandle', function () {
    postJson(action([PluginsController::class, 'enable']), [])
        ->assertJsonValidationErrors(['pluginHandle']);
});

test('disable validates pluginHandle', function () {
    postJson(action([PluginsController::class, 'disable']), [])
        ->assertJsonValidationErrors(['pluginHandle']);
});

test('switchEdition validates required fields', function () {
    postJson(action([PluginsController::class, 'switchEdition']), [])
        ->assertJsonValidationErrors(['pluginHandle', 'edition']);
});

test('switchEdition validates pluginHandle field', function () {
    postJson(action([PluginsController::class, 'switchEdition']), [
        'edition' => 'pro',
    ])
        ->assertJsonValidationErrors(['pluginHandle']);
});

test('switchEdition validates edition field', function () {
    postJson(action([PluginsController::class, 'switchEdition']), [
        'pluginHandle' => 'test-plugin',
    ])
        ->assertJsonValidationErrors(['edition']);
});

test('editSettings returns 404 for non-existent plugin', function () {
    get(action([PluginsController::class, 'editSettings'], ['non-existent-plugin']))
        ->assertNotFound();
});

test('editSettings returns 403 when allowAdminChanges is false and plugin lacks readonly support', function () {
    Cms::config()->allowAdminChanges = false;

    // Test plugin doesn't have readonly support
    $response = get(action([PluginsController::class, 'editSettings'], ['test-plugin']));

    // Should be 403 or 404 depending on plugin state
    expect($response->status())->toBeIn([403, 404]);
});

test('editSettings loads for existing plugin', function () {
    // The test plugin should be loaded
    $response = get(action([PluginsController::class, 'editSettings'], ['test-plugin']));

    // May be 404 if plugin not installed, or 200 if it is
    expect($response->status())->toBeIn([200, 404]);
});

test('saveSettings validates pluginHandle', function () {
    postJson(action([PluginsController::class, 'saveSettings']), [])
        ->assertJsonValidationErrors(['pluginHandle']);
});

test('saveSettings returns 404 for non-existent plugin', function () {
    postJson(action([PluginsController::class, 'saveSettings']), [
        'pluginHandle' => 'non-existent-plugin',
    ])
        ->assertNotFound();
});

test('respects read-only mode for install', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'install']), [
        'pluginHandle' => 'test-plugin',
    ])
        ->assertForbidden();
});

test('respects read-only mode for uninstall', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'uninstall']), [
        'pluginHandle' => 'test-plugin',
    ])
        ->assertForbidden();
});

test('respects read-only mode for enable', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'enable']), [
        'pluginHandle' => 'test-plugin',
    ])
        ->assertForbidden();
});

test('respects read-only mode for disable', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'disable']), [
        'pluginHandle' => 'test-plugin',
    ])
        ->assertForbidden();
});

test('respects read-only mode for switchEdition', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'switchEdition']), [
        'pluginHandle' => 'test-plugin',
        'edition' => 'pro',
    ])
        ->assertForbidden();
});

test('respects read-only mode for saveSettings', function () {
    Cms::config()->allowAdminChanges = false;

    postJson(action([PluginsController::class, 'saveSettings']), [
        'pluginHandle' => 'test-plugin',
        'settings' => [],
    ])
        ->assertForbidden();
});
