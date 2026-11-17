<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Gate;

use function CraftCms\Cms\cp_url;

beforeEach(function () {
    app()->forgetInstance('routes.cached');
    app()->forgetInstance(TestPlugin::class);

    $plugin = TestPlugin::create([
        'handle' => 'test-plugin',
        'name' => 'Test Plugin',
        'hasCpSection' => true,
    ]);
    $plugin->useBasePath(dirname(__DIR__, 2).'/TestClasses/TestPlugin/src');
    $plugin->bootHasRoutes();

    Gate::define('testPluginPermission', fn ($user) => $user->can('editUsers'));
});

afterEach(function () {
    app()->forgetInstance('routes.cached');
    app()->forgetInstance(TestPlugin::class);
});

dataset('plugin cp endpoints', [
    'cp page' => ['GET', 'plugin-cp', 'cp'],
    'cp action' => ['POST', 'actions/test-plugin/plugin-action', 'action'],
]);

it('redirects guests to the CP login before route authorization', function (string $method, string $path) {
    $this->call($method, cp_url($path))->assertRedirect(cp_url('login'));
    $this->call($method, cp_url("$path-restricted"))->assertRedirect(cp_url('login'));
})->with('plugin cp endpoints');

it('returns unauthorized for JSON requests from guests', function (string $method, string $path) {
    $this->json($method, cp_url($path))->assertUnauthorized();
    $this->json($method, cp_url("$path-restricted"))->assertUnauthorized();
})->with('plugin cp endpoints');

it('denies authenticated users without CP access', function (string $method, string $path) {
    $this->actingAs(User::factory()->active()->create());

    $this->call($method, cp_url($path))->assertForbidden();
})->with('plugin cp endpoints');

it('allows CP users without requiring plugin section access', function (string $method, string $path, string $body) {
    $this->actingAs(User::factory()->active()->withPermissions(['accessCp'])->create());

    $this->call($method, cp_url($path))->assertOk()->assertContent($body);
    $this->call($method, cp_url("$path-restricted"))->assertForbidden();
})->with('plugin cp endpoints');

it('allows users with CP access and the route permission', function (string $method, string $path) {
    $this->actingAs(User::factory()->active()->withPermissions(['accessCp', 'viewUsers', 'editUsers'])->create());

    $this->call($method, cp_url("$path-restricted"))->assertOk();
})->with('plugin cp endpoints');

it('keeps site actions public and honors their route permissions', function () {
    $prefix = '/'.trim(Cms::config()->actionTrigger, '/').'/test-plugin';

    $this->post("$prefix/plugin-action")->assertOk()->assertContent('action');
    $this->post("$prefix/plugin-action-restricted")->assertForbidden();
});

it('protects plugin CP routes when the CP trigger is null', function () {
    Cms::config()->cpTrigger = null;
    app(TestPlugin::class)->bootHasRoutes();
    $loginUrl = cp_url('login');

    $this->get(cp_url('plugin-cp'))->assertRedirect($loginUrl);
    $this->post(cp_url('actions/test-plugin/plugin-action'))->assertRedirect($loginUrl);
});
