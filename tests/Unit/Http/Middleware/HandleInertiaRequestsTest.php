<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\File;
use Illuminate\Http\Request;

it('changes its asset version when the control panel\'s or a plugin\'s Vite build is republished', function () {
    $publicPath = storage_path('framework/testing/inertia-assets');
    $originalPublicPath = public_path();
    $version = fn () => app(HandleInertiaRequests::class)->version(Request::create('/admin'));

    File::deleteDirectory($publicPath);
    File::ensureDirectoryExists("{$publicPath}/vendor/craft/build");
    File::ensureDirectoryExists("{$publicPath}/vendor/acme/plugin/build");
    File::put("{$publicPath}/vendor/craft/build/manifest.json", '{"resources/js/cp.ts":{"file":"assets/cp-1.js"}}');
    File::put("{$publicPath}/vendor/acme/plugin/build/manifest.json", '{"resources/js/plugin.ts":{"file":"assets/plugin-1.js"}}');
    app()->usePublicPath($publicPath);
    app(Plugins::class)->addViteConfig('acme/plugin', [
        'hotFile' => "{$publicPath}/hot",
        'buildDirectory' => 'vendor/acme/plugin/build/',
        'input' => ['resources/js/plugin.ts'],
    ]);

    try {
        $initial = $version();

        File::put("{$publicPath}/vendor/acme/plugin/build/manifest.json", '{"resources/js/plugin.ts":{"file":"assets/plugin-2.js"}}');
        $afterPluginUpdate = $version();

        File::put("{$publicPath}/vendor/craft/build/manifest.json", '{"resources/js/cp.ts":{"file":"assets/cp-2.js"}}');

        expect($initial)->toBeString()
            ->and($afterPluginUpdate)->not->toBe($initial)
            ->and($version())->not->toBe($afterPluginUpdate);
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($publicPath);
    }
});

it('shares the CP\'s Vue, Inertia and components with plugin bundles through the import map', function () {
    $publicPath = storage_path('framework/testing/inertia-assets');
    $originalPublicPath = public_path();

    File::deleteDirectory($publicPath);
    File::ensureDirectoryExists("{$publicPath}/vendor/craft/build");
    File::put("{$publicPath}/vendor/craft/build/manifest.json", '{"resources/js/vue.ts":{"file":"assets/vue-abc123.js","isEntry":true},"resources/js/cp-module.ts":{"file":"assets/cp-module-def456.js","isEntry":true},"resources/js/garnish.ts":{"file":"assets/garnish-ghi789.js","isEntry":true},"resources/js/inertia.ts":{"file":"assets/inertia-jkl012.js","isEntry":true}}');
    app()->usePublicPath($publicPath);

    try {
        expect(Cp::sharedModules())->toHaveKey('vue')
            ->and(Cp::sharedModules()['vue'])->toEndWith('/vendor/craft/build/assets/vue-abc123.js')
            ->and(Cp::sharedModules()['@craftcms/cp'])->toEndWith('/vendor/craft/build/assets/cp-module-def456.js')
            ->and(Cp::sharedModules()['@craftcms/garnish'])->toEndWith('/vendor/craft/build/assets/garnish-ghi789.js')
            ->and(Cp::sharedModules()['@inertiajs/vue3'])->toEndWith('/vendor/craft/build/assets/inertia-jkl012.js');
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($publicPath);
    }
});
