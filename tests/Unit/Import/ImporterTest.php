<?php

declare(strict_types=1);

use CraftCms\Cms\Import\Importer;
use CraftCms\Cms\Import\ImportServiceProvider;
use Illuminate\Support\Facades\Log;

it('registers the import log channel without any app-level config', function () {
    expect(config('logging.channels.import'))->toMatchArray([
        'driver' => 'daily',
        'path' => storage_path('logs/import.log'),
    ]);
});

it('does not override a consumer-defined import channel', function () {
    config()->set('logging.channels.import', ['driver' => 'single', 'path' => 'custom.log']);

    app()->register(ImportServiceProvider::class, force: true);

    expect(config('logging.channels.import'))->toBe(['driver' => 'single', 'path' => 'custom.log']);
});

it('routes warning messages to the import channel', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('import')
        ->andReturnSelf();
    Log::shouldReceive('warning')
        ->once()
        ->with('something went wrong', ['foo' => 'bar']);

    app(Importer::class)->warning('something went wrong', ['foo' => 'bar']);
});

it('routes error messages to the import channel', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('import')
        ->andReturnSelf();
    Log::shouldReceive('error')
        ->once()
        ->with('it broke', []);

    app(Importer::class)->error('it broke');
});

it('routes info messages to the import channel', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('import')
        ->andReturnSelf();
    Log::shouldReceive('info')
        ->once()
        ->with('fyi', []);

    app(Importer::class)->info('fyi');
});
