<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Cms::setIsInstalled(false);
});

it('aborts when Craft is already installed', function () {
    Cms::setIsInstalled();
    Config::set('app.debug', false);

    get(action([InstallController::class, 'index']))->assertNotFound();
});

it('shows the install page', function () {
    Cms::setIsInstalled(false);

    get(action([InstallController::class, 'index']))
        ->assertInertia(function (AssertableInertia $page) {
            $page->component('Install')
                ->missing('licenseHtml')
                ->loadDeferredProps(function (AssertableInertia $reload) {
                    $reload->has('licenseHtml')
                        ->where('licenseHtml', fn ($html) => str_contains($html, 'Copyright © Pixel &amp; Tonic, Inc.'));
                });
        })
        ->assertOk();
});

it('can validate the db', function () {
    $connection = config('database.default');

    postJson(action([InstallController::class, 'validateDb']), [
        'driver' => Config::get("database.connections.{$connection}.driver"),
        'host' => Config::get("database.connections.{$connection}.host"),
        'database' => 'a non existing database',
        'port' => Config::get("database.connections.{$connection}.port"),
        'username' => Config::get("database.connections.{$connection}.username"),
        'password' => Config::get("database.connections.{$connection}.password"),
        'prefix' => Config::get("database.connections.{$connection}.prefix"),
        'schema' => Config::get("database.connections.{$connection}.schema"),
    ])->assertUnprocessable()
        ->assertSee('PDO exception: ');

    postJson(action([InstallController::class, 'validateDb']), [
        'driver' => Config::get("database.connections.{$connection}.driver"),
        'host' => Config::get("database.connections.{$connection}.host"),
        'database' => Config::get("database.connections.{$connection}.database"),
        'port' => Config::get("database.connections.{$connection}.port"),
        'username' => Config::get("database.connections.{$connection}.username"),
        'password' => Config::get("database.connections.{$connection}.password"),
        'prefix' => Config::get("database.connections.{$connection}.prefix"),
        'schema' => Config::get("database.connections.{$connection}.schema"),
    ])->assertOk();
});

it('can validate account', function (array $data, array $errors) {
    postJson(action([InstallController::class, 'validateAccount']), $data)
        ->assertJsonValidationErrors($errors);
})->with([
    [
        'data' => [],
        'errors' => ['email', 'username', 'password'],
    ],
    [
        'data' => [
            'email' => 'not-an-email',
            'username' => 'admin',
            'password' => 'asupersecretpassword',
        ],
        'errors' => ['email'],
    ],
    [
        'data' => [
            'email' => 'support@pixelandtonic.com',
            'username' => 'invalid username',
            'password' => 'asupersecretpassword',
        ],
        'errors' => ['username'],
    ],
    [
        'data' => [
            'email' => 'support@pixelandtonic.com',
            'username' => 'admin',
            'password' => 'short',
        ],
        'errors' => ['password'],
    ],
]);

test('username is not required when useEmailAsUsername is enabled', function () {
    Cms::config()->useEmailAsUsername();

    postJson(action([InstallController::class, 'validateAccount']), [])
        ->assertJsonValidationErrors([
            'email',
            'password',
        ]);
});

it('can validate site', function (array $data, array $errors) {
    postJson(action([InstallController::class, 'validateSite']), $data)
        ->when(
            empty($errors),
            fn (TestResponse $response) => $response->assertOk(),
            fn (TestResponse $response) => $response->assertJsonValidationErrors($errors),
        );
})->with([
    [
        'data' => [
            'name' => 'Craft',
            'baseUrl' => 'http://localhost',
            'language' => 'en',
        ],
        'errors' => [],
    ],
    [
        'data' => [],
        'errors' => ['name', 'language'],
    ],
    [
        'data' => [
            'name' => [],
            'baseUrl' => 'http://localhost',
            'language' => 'en',
        ],
        'errors' => ['name'],
    ],
    [
        'data' => [
            'name' => 'Craft',
            'baseUrl' => 'not-an-url',
            'language' => 'en',
        ],
        'errors' => ['baseUrl'],
    ],
    [
        'data' => [
            'name' => 'Craft',
            'baseUrl' => 'http://localhost',
            'language' => 'not-a-language',
        ],
        'errors' => ['language'],
    ],
]);
