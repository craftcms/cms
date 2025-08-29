<?php

use CraftCms\Aliases\Facades\Aliases;
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
