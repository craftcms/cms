<?php

use CraftCms\Aliases\Facades\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Support\Env;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::ensureDirectoryExists(__DIR__.'/tmp');
});

afterEach(function () {
    File::deleteDirectory(__DIR__.'/tmp');
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
    define('CRAFT_TESTS_PATH', __DIR__);

    expect(Env::parse(null))->toBeNull();
    expect(Env::parse('$CRAFT_TESTS_PATH'))->toBe(CRAFT_TESTS_PATH);
    expect(Env::parse('$CRAFT_TESTS_PATH/foo/bar'))->toBe(CRAFT_TESTS_PATH.'/foo/bar');
    expect(Env::parse('CRAFT_TESTS_PATH'))->toBe('CRAFT_TESTS_PATH');
    expect(Env::parse('$TEST_MISSING'))->toBeNull();
    expect(Env::parse('@vendor/foo/bar'))->toBe(Aliases::get('@vendor/foo/bar'));
});

test('parseBoolean', function (?bool $expected, mixed $value) {
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
    [false, ''],
    [null, 'whatever'],
    [true, 1],
    [false, 0],
    [null, 2],
    [null, '$TEST_MISSING'],
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
    ['foo,bar', 'disabledPlugins', 'CRAFT_DISABLED_PLUGINS', 'foo,bar'],
    ['*', 'disabledPlugins', 'CRAFT_DISABLED_PLUGINS', '*'],
    [1, 'defaultWeekStartDay', 'CRAFT_DEFAULT_WEEK_START_DAY', '1'],
    ['login,with,comma', 'loginPath', 'CRAFT_LOGIN_PATH', 'login,with,comma'],
    [false, 'loginPath', 'CRAFT_LOGIN_PATH', 'false'],
]);
