<?php

use CraftCms\Cms\Support\Env;

it('can get env', function () {
    $_SERVER['TEST_SERVER_ENV'] = 'server';
    expect(Env::get('TEST_SERVER_ENV'))->toBe('server');
    unset($_SERVER['TEST_SERVER_ENV']);

    putenv('TEST_GETENV_ENV=getenv');
    expect(Env::get('TEST_GETENV_ENV'))->toBe('getenv');
    putenv('TEST_GETENV_ENV');

    putenv('TEST_GETENV_TRUE_ENV=true');
    expect(Env::get('TEST_GETENV_TRUE_ENV'))->toBeTrue();
    putenv('TEST_GETENV_TRUE_ENV');

    putenv('TEST_GETENV_FALSE_ENV=false');
    expect(Env::get('TEST_GETENV_FALSE_ENV'))->toBeFalse();
    putenv('TEST_GETENV_FALSE_ENV');

    define('TEST_CONST', 'const');

    expect(Env::get('TEST_CONST'))->toBe('const');
    expect(Env::get('TEST_NONEXISTENT_ENV'))->toBeNull();

    putenv('SHH=foo');
    expect(Env::get('SHH'))->toBe('foo');
    putenv('SHH');
});
