<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::ensureDirectoryExists(__DIR__.'/tmp');
});

afterEach(function () {
    File::deleteDirectory(__DIR__.'/tmp');
});

it('can get env', function () {
    $_SERVER['TEST_SERVER_ENV'] = 'server';
    expect(Env::get('TEST_SERVER_ENV'))->toBe('server');
    unset($_SERVER['TEST_SERVER_ENV']);

    $variables = [
        'TEST_1' => 'testing1',
        'TEST_2' => 'foo-${TEST_1}-bar',
        'TEST_3' => 'true',
        'TEST_4' => 'false',
    ];

    foreach ($variables as $name => $value) {
        putenv("$name=$value");
    }

    expect(Env::get('TEST_1'))->toBe('testing1');
    expect(Env::get('TEST_2'))->toBe('foo-testing1-bar');
    expect(Env::get('TEST_3'))->toBeTrue();
    expect(Env::get('TEST_4'))->toBeFalse();

    foreach (array_keys($variables) as $name) {
        putenv($name);
    }

    define('TEST_CONST', 'const');

    expect(Env::get('TEST_CONST'))->toBe('const');
    expect(Env::get('TEST_NONEXISTENT_ENV'))->toBeNull();
});

it('can remove a variable from a file', function () {
    $filesystem = new Filesystem;
    $path = __DIR__.'/tmp/env-test-file';
    $filesystem->put($path, implode(PHP_EOL, [
        'APP_NAME=Laravel',
        'APP_ENV=local',
        'APP_KEY=base64:randomkey',
        'APP_DEBUG=true',
        'APP_URL=http://localhost',
        '',
        'DB_CONNECTION=mysql',
        'DB_HOST=',
    ]));

    Env::removeVariable('APP_DEBUG', $path);

    $this->assertSame(
        implode(PHP_EOL, [
            'APP_NAME=Laravel',
            'APP_ENV=local',
            'APP_KEY=base64:randomkey',
            'APP_URL=http://localhost',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST=',
        ]),
        $filesystem->get($path)
    );
});

test('parse', function () {
    $variables = [
        'TEST_1' => 'testing1',
        'TEST_2' => 'foo${TEST_1}bar',
        'TEST_DEFAULT_SITE_API_KEY' => 'abcdef',
    ];

    foreach ($variables as $name => $value) {
        putenv("$name=$value");
    }

    define('CRAFT_TESTS_PATH', __DIR__);

    expect(Env::parse('$TEST_1'))->toBe('testing1');
    expect(Env::parse('${TEST_1}'))->toBe('testing1');
    expect(Env::parse('$TEST_1/foo/bar'))->toBe('testing1/foo/bar');
    expect(Env::parse('foo/$TEST_1/bar'))->toBe('foo/testing1/bar');
    expect(Env::parse('$TEST_2'))->toBe('footesting1bar');
    expect(Env::parse('${TEST_2}'))->toBe('footesting1bar');
    expect(Env::parse('foo/$TEST_2/bar'))->toBe('foo/footesting1bar/bar');

    /**
     * Temporary solution, as Sites is still a Yii service and not
     * has been called yet when running tests purely in Laravel
     */
    if (Env::parse('$CRAFT_SITE') === null) {
        DB::table('info')->first();
        Sites::setCurrentSite(
            Sites::getSiteByHandle('defaultSite')
        );
    }

    expect(Env::parse('$CRAFT_SITE'))->toBe('defaultSite');
    expect(Env::parse('$CRAFT_SITE_UPPER'))->toBe('DEFAULT_SITE');

    expect(Env::parse('$TEST_${CRAFT_SITE_UPPER}_API_KEY'))->toBe('abcdef');
    expect(Env::parse('$CRAFT_TESTS_PATH'))->toBe(CRAFT_TESTS_PATH);
    expect(Env::parse('$CRAFT_TESTS_PATH/foo/bar'))->toBe(CRAFT_TESTS_PATH.'/foo/bar');
    expect(Env::parse('CRAFT_TESTS_PATH'))->toBe('CRAFT_TESTS_PATH');
    expect(Env::parse('@vendor/foo/bar'))->toBe(Aliases::get('@vendor/foo/bar'));
    expect(Env::parse('$TEST_MISSING'))->toBeNull();
    expect(Env::parse(null))->toBeNull();

    // https://github.com/craftcms/cms/issues/19522
    expect(Env::parse('$58 million'))->toBe('$58 million');
    expect(Env::parse('the $58 million'))->toBe('the $58 million');
    expect(Env::parse('the $58'))->toBe('the $58');
    expect(Env::parse('$58/test'))->toBe('/test');
    expect(Env::parse('test/$58/test'))->toBe('test//test');
    expect(Env::parse('test/$58'))->toBe('test/');

    foreach (array_keys($variables) as $name) {
        putenv($name);
    }
});

test('parseBoolean', function (?bool $expected, mixed $value, array $values = []) {
    foreach ($values as $name => $v) {
        putenv("$name=$v");
    }
    expect(Env::parseBoolean($value))->toBe($expected);
})->with([
    [true, true],
    [false, false],
    [true, 'yes'],
    [false, 'no'],
    [true, 'on'],
    [false, 'off'],
    [true, '1'],
    [false, '0'],
    [true, 'true'],
    [false, 'false'],
    [null, ''],
    [null, 'whatever'],
    [true, 1],
    [false, 0],
    [null, 2],
    [null, '$TEST_MISSING'],
    [
        false,
        '$TEST_FALSE',
        ['TEST_FALSE' => 'false'],
    ],
    [
        true,
        '$TEST_TRUE',
        ['TEST_TRUE' => 'true'],
    ],
]);

test('config', function (mixed $expected, string $paramName, string $overrideName, mixed $overrideValue) {
    $envString = $overrideName;

    if ($overrideValue !== null) {
        $envString .= "=$overrideValue";
    }

    putenv($envString);

    $config = Env::config(GeneralConfig::class, 'CRAFT_');
    if ($expected === null) {
        expect($config)->not()->toHaveKey($paramName);
    } else {
        expect($config)->toHaveKey($paramName);
        expect($config[$paramName])->toEqual($expected);
    }

    // Cleanup env for subsequent tests
    putenv($overrideName);
})->with([
    [false, 'allowAdminChanges', 'CRAFT_ALLOW_ADMIN_CHANGES', 'false'],
    [null, 'allowAdminChanges', 'CRAFT_ALLOW_ADMIN_CHANGES', null],
    ['remote', 'defaultAssetTransformer', 'CRAFT_DEFAULT_ASSET_TRANSFORMER', 'remote'],
    ['foo,bar', 'disabledPlugins', 'CRAFT_DISABLED_PLUGINS', 'foo,bar'],
    ['*', 'disabledPlugins', 'CRAFT_DISABLED_PLUGINS', '*'],
    [1, 'defaultWeekStartDay', 'CRAFT_DEFAULT_WEEK_START_DAY', '1'],
    ['login,with,comma', 'loginPath', 'CRAFT_LOGIN_PATH', 'login,with,comma'],
    [false, 'loginPath', 'CRAFT_LOGIN_PATH', 'false'],
]);
