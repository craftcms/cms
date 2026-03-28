<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('prints garbage collection progress when run from the command', function () {
    expect(Artisan::call('craft:gc:run'))->toBe(0)
        ->and(Artisan::output())->toContain('Running garbage collection ...');
});

it('supports silent garbage collection output', function () {
    expect(Artisan::call('craft:gc:run', ['--silent' => true]))->toBe(0)
        ->and(Artisan::output())->toBe('');
});
