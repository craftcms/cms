<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\ViewServiceProvider;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Artisan;

use function CraftCms\Cms\craftAsset;

it('keeps the console in cp template mode after a queue worker resets its scope', function () {
    Artisan::call('queue:work', ['--queue' => 'empty', '--stop-when-empty' => true]);

    expect(TemplateMode::get())->toBe(TemplateMode::Cp);
});

it('does not override the host application Vite hot file when registering views', function () {
    $hotFile = public_path('hot');

    app(Vite::class)->useHotFile($hotFile);

    app(ViewServiceProvider::class, ['app' => app()])->register();

    expect(app(Vite::class)->hotFile())->toBe($hotFile);
});

it('does not leak Craft asset Vite settings to the host application', function () {
    $hotFile = public_path('hot');

    app(Vite::class)->useHotFile($hotFile);

    craftAsset('resources/js/cp.ts');

    expect(app(Vite::class)->hotFile())->toBe($hotFile);
});
