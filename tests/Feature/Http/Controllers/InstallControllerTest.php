<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\ConnectionConfig;
use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Cms::setIsInstalled(false);
});

afterEach(function () {
    putenv('INSTALL_TIMEZONE');
    putenv('INSTALL_BASE_URL');
});

it('503s if debug is disabled', function () {
    config()->set('app.debug', false);

    get(action([InstallController::class, 'index']))->assertServiceUnavailable();
});

it('shows the install page', function () {
    Cms::setIsInstalled(false);

    get(action([InstallController::class, 'index']))
        ->assertInertia(function (AssertableInertia $page) {
            $page->component('install/Install')
                ->missing('licenseHtml')
                ->missing('localeOptions')
                ->missing('timezone')
                ->loadDeferredProps(function (AssertableInertia $reload) {
                    $reload->has('licenseHtml')
                        ->where('licenseHtml', fn ($html) => str_contains($html, 'Copyright © Pixel &amp; Tonic, Inc.'))
                        ->has('localeOptions')
                        ->has('timezone')
                        ->where('timezone', fn ($options) => collect($options)->pluck('value')->contains('America/New_York'));
                });
        })
        ->assertOk();
});

it('shows the install page during Laravel maintenance', function () {
    app()->maintenanceMode()->activate([]);

    get(action([InstallController::class, 'index']))
        ->assertOk();
});

it('provides driver-specific db defaults to the install page', function () {
    get(action([InstallController::class, 'index']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('dbDefaults.sqlite.database', 'database.sqlite')
            ->where('dbDefaults.mysql.port', '3306')
            ->where('dbDefaults.pgsql.port', '5432')
            ->missing('dbConfig.database'))
        ->assertOk();
});

it('can validate the db', function () {
    $connection = config('database.default');
    $driver = Config::get("database.connections.{$connection}.driver");

    $response = postJson(action([InstallController::class, 'validateDb']), [
        'driver' => $driver,
        'host' => Config::get("database.connections.{$connection}.host"),
        'database' => $driver === 'sqlite' ? storage_path('runtime') : 'a non existing database',
        'port' => Config::get("database.connections.{$connection}.port"),
        'username' => Config::get("database.connections.{$connection}.username"),
        'password' => Config::get("database.connections.{$connection}.password"),
        'prefix' => Config::get("database.connections.{$connection}.prefix"),
        'schema' => Config::get("database.connections.{$connection}.schema"),
    ])->assertUnprocessable();

    $response->assertSee($driver === 'sqlite' ? 'is a directory' : 'PDO exception: ');

    $database = $driver === 'sqlite'
        ? storage_path('runtime/validate-sqlite-'.uniqid().'.sqlite')
        : Config::get("database.connections.{$connection}.database");

    try {
        postJson(action([InstallController::class, 'validateDb']), [
            'driver' => Config::get("database.connections.{$connection}.driver"),
            'host' => Config::get("database.connections.{$connection}.host"),
            'database' => $database,
            'port' => Config::get("database.connections.{$connection}.port"),
            'username' => Config::get("database.connections.{$connection}.username"),
            'password' => Config::get("database.connections.{$connection}.password"),
            'prefix' => Config::get("database.connections.{$connection}.prefix"),
            'schema' => Config::get("database.connections.{$connection}.schema"),
        ])->assertOk();
    } finally {
        if ($driver === 'sqlite') {
            File::delete($database);
        }
    }
});

it('can validate and create a sqlite database file', function () {
    $databasePath = storage_path('runtime/install-sqlite-'.uniqid().'.sqlite');

    File::delete($databasePath);

    postJson(action([InstallController::class, 'validateDb']), [
        'driver' => 'sqlite',
        'database' => $databasePath,
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => 'secret',
        'schema' => 'public',
    ])->assertOk();

    expect(File::isFile($databasePath))->toBeTrue();

    File::delete($databasePath);
});

it('defaults a blank sqlite database to the sqlite install database path', function () {
    $data = app(InstallController::class)->validateDbData([
        'driver' => 'sqlite',
    ]);

    expect($data)
        ->toMatchArray([
            'driver' => 'sqlite',
            'database' => ConnectionConfig::normalizeSqliteDatabasePath('database.sqlite'),
        ]);
});

it('defaults missing database ports to integers for server-backed drivers', function (string $driver, int $port) {
    $data = app(InstallController::class)->validateDbData([
        'driver' => $driver,
    ]);

    expect($data['port'])->toBe($port);
})->with([
    'mysql' => ['mysql', 3306],
    'mariadb' => ['mariadb', 3306],
    'pgsql' => ['pgsql', 5432],
]);

it('normalizes sqlite install db config without server credentials', function () {
    $data = app(InstallController::class)->validateDbData([
        'driver' => 'sqlite',
        'database' => 'database/install.sqlite',
        'host' => '127.0.0.1',
        'port' => 3306,
        'username' => 'root',
        'password' => 'secret',
        'schema' => 'public',
    ]);

    expect($data)
        ->toMatchArray([
            'driver' => 'sqlite',
            'database' => ConnectionConfig::normalizeSqliteDatabasePath('database/install.sqlite'),
            'foreign_key_constraints' => true,
            'busy_timeout' => 5000,
            'journal_mode' => 'wal',
            'pragmas' => [
                'cache_size' => -20000,
                'mmap_size' => 2147483648,
                'temp_store' => 'MEMORY',
            ],
            'synchronous' => 'normal',
        ])
        ->and(array_key_exists('host', $data))->toBeFalse()
        ->and(array_key_exists('port', $data))->toBeFalse()
        ->and(array_key_exists('username', $data))->toBeFalse()
        ->and(array_key_exists('password', $data))->toBeFalse()
        ->and(array_key_exists('schema', $data))->toBeFalse();
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
    'valid timezone' => [
        'data' => [
            'name' => 'Craft',
            'baseUrl' => 'http://localhost',
            'language' => 'en',
            'timezone' => 'America/New_York',
        ],
        'errors' => [],
    ],
    'invalid timezone' => [
        'data' => [
            'name' => 'Craft',
            'baseUrl' => 'http://localhost',
            'language' => 'en',
            'timezone' => 'Not/A_Timezone',
        ],
        'errors' => ['timezone'],
    ],
]);

it('accepts environment variables for the timezone and baseUrl when validating site', function () {
    putenv('INSTALL_TIMEZONE=America/New_York');
    putenv('INSTALL_BASE_URL=https://example.test');

    postJson(action([InstallController::class, 'validateSite']), [
        'name' => 'Craft',
        'baseUrl' => '$INSTALL_BASE_URL',
        'language' => 'en',
        'timezone' => '$INSTALL_TIMEZONE',
    ])->assertOk();
});

it('validates resolved environment variable timezone values when validating site', function () {
    putenv('INSTALL_TIMEZONE=super-secret-not-a-timezone');

    $response = postJson(action([InstallController::class, 'validateSite']), [
        'name' => 'Craft',
        'baseUrl' => 'http://localhost',
        'language' => 'en',
        'timezone' => '$INSTALL_TIMEZONE',
    ])->assertJsonValidationErrors('timezone');

    $response->assertDontSee('super-secret-not-a-timezone');
});

it('validates resolved environment variable baseUrl values when validating site', function () {
    putenv('INSTALL_BASE_URL=not-an-url');

    postJson(action([InstallController::class, 'validateSite']), [
        'name' => 'Craft',
        'baseUrl' => '$INSTALL_BASE_URL',
        'language' => 'en',
    ])->assertJsonValidationErrors('baseUrl');
});

test('Laravel migration installer can recreate the sessions table', function () {
    expect(Schema::hasTable('sessions'))->toBeTrue();

    Schema::drop('sessions');

    expect(Schema::hasTable('sessions'))->toBeFalse();

    app(LaravelMigrations::class)->ensureSessionsTable();

    expect(Schema::hasTable('sessions'))->toBeTrue();
});
