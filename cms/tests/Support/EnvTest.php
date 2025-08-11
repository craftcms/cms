<?php

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
