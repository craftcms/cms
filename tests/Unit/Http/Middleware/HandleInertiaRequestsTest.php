<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Http\Middleware\HandleInertiaRequests;
use CraftCms\Cms\Support\File;
use Illuminate\Http\Request;

it('uses the published Craft Vite manifest as its asset version', function () {
    $publicPath = storage_path('framework/testing/inertia-assets');
    $originalPublicPath = public_path();
    $manifest = '{"resources/js/cp.ts":{"file":"assets/cp.js"}}';

    File::deleteDirectory($publicPath);
    File::ensureDirectoryExists("{$publicPath}/vendor/craft/build");
    File::put("{$publicPath}/vendor/craft/build/manifest.json", $manifest);
    app()->usePublicPath($publicPath);

    try {
        expect(app(HandleInertiaRequests::class)->version(Request::create('/admin')))
            ->toBe(md5($manifest));
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($publicPath);
    }
});

it('shares the CP\'s Vue build, element index and admin table with plugin bundles through the import map', function () {
    $publicPath = storage_path('framework/testing/inertia-assets');
    $originalPublicPath = public_path();

    File::deleteDirectory($publicPath);
    File::ensureDirectoryExists("{$publicPath}/vendor/craft/build");
    File::put("{$publicPath}/vendor/craft/build/manifest.json", '{"resources/js/vue.ts":{"file":"assets/vue-abc123.js","isEntry":true},"resources/js/elements.ts":{"file":"assets/elements-def456.js","isEntry":true},"resources/js/admin-table.ts":{"file":"assets/admin-table-ghi789.js","isEntry":true}}');
    app()->usePublicPath($publicPath);

    try {
        expect(Cp::sharedModules())->toHaveKey('vue')
            ->and(Cp::sharedModules()['vue'])->toEndWith('/vendor/craft/build/assets/vue-abc123.js')
            ->and(Cp::sharedModules()['@craftcms/cms/elements'])->toEndWith('/vendor/craft/build/assets/elements-def456.js')
            ->and(Cp::sharedModules()['@craftcms/cms/admin-table'])->toEndWith('/vendor/craft/build/assets/admin-table-ghi789.js');
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($publicPath);
    }
});
