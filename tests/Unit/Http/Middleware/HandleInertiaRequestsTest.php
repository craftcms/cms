<?php

declare(strict_types=1);

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
