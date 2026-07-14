<?php

declare(strict_types=1);

use CraftCms\Cms\Plugin\PluginPackageManifest;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

beforeEach(function () {
    $this->filesystem = new Filesystem;
    $this->basePath = sys_get_temp_dir().'/craft-plugin-package-manifest-'.bin2hex(random_bytes(8));
    $this->manifestPath = "{$this->basePath}/bootstrap/cache/packages.php";

    $this->filesystem->ensureDirectoryExists("{$this->basePath}/vendor/composer");
    $this->filesystem->ensureDirectoryExists(dirname($this->manifestPath));
});

afterEach(function () {
    $this->filesystem->deleteDirectory($this->basePath);
});

it('appends detected craft plugin providers to existing Laravel metadata', function () {
    $manifest = buildPluginPackageManifest($this->filesystem, $this->basePath, $this->manifestPath, [[
        'name' => 'vendor/craft-plugin',
        'type' => 'craft-plugin',
        'extra' => [
            'class' => PluginPackageManifestTestPlugin::class,
            'laravel' => [
                'providers' => [
                    PluginPackageManifestExistingProvider::class,
                ],
                'aliases' => [
                    'ExistingAlias' => PluginPackageManifestExistingProvider::class,
                ],
            ],
        ],
    ]]);

    expect($manifest)->toBe([
        'vendor/craft-plugin' => [
            'providers' => [
                PluginPackageManifestExistingProvider::class,
                PluginPackageManifestTestPlugin::class,
            ],
            'aliases' => [
                'ExistingAlias' => PluginPackageManifestExistingProvider::class,
            ],
        ],
    ]);
});

it('dedupes detected craft plugin providers already in Laravel metadata', function () {
    $manifest = buildPluginPackageManifest($this->filesystem, $this->basePath, $this->manifestPath, [[
        'name' => 'vendor/craft-plugin',
        'type' => 'craft-plugin',
        'extra' => [
            'class' => PluginPackageManifestTestPlugin::class,
            'laravel' => [
                'providers' => [
                    PluginPackageManifestExistingProvider::class,
                    PluginPackageManifestTestPlugin::class,
                    PluginPackageManifestTestPlugin::class,
                ],
            ],
        ],
    ]]);

    expect($manifest)->toBe([
        'vendor/craft-plugin' => [
            'providers' => [
                PluginPackageManifestExistingProvider::class,
                PluginPackageManifestTestPlugin::class,
            ],
        ],
    ]);
});

it('preserves craft plugin metadata when no provider class is detected', function () {
    $manifest = buildPluginPackageManifest($this->filesystem, $this->basePath, $this->manifestPath, [[
        'name' => 'vendor/craft-plugin',
        'type' => 'craft-plugin',
        'extra' => [
            'laravel' => [
                'aliases' => [
                    'ExistingAlias' => PluginPackageManifestExistingProvider::class,
                ],
            ],
        ],
    ]]);

    expect($manifest)->toBe([
        'vendor/craft-plugin' => [
            'aliases' => [
                'ExistingAlias' => PluginPackageManifestExistingProvider::class,
            ],
        ],
    ]);
});

it('honors dont-discover after merging craft plugin providers', function () {
    $manifest = buildPluginPackageManifest($this->filesystem, $this->basePath, $this->manifestPath, [[
        'name' => 'vendor/craft-plugin',
        'type' => 'craft-plugin',
        'extra' => [
            'class' => PluginPackageManifestTestPlugin::class,
        ],
    ]], [
        'dont-discover' => [
            'vendor/craft-plugin',
        ],
    ]);

    expect($manifest)->toBe([]);
});

function buildPluginPackageManifest(
    Filesystem $filesystem,
    string $basePath,
    string $manifestPath,
    array $packages,
    array $laravelConfig = [],
): array {
    $filesystem->put("{$basePath}/composer.json", json_encode([
        'extra' => [
            'laravel' => $laravelConfig,
        ],
    ], JSON_THROW_ON_ERROR));

    $filesystem->put("{$basePath}/vendor/composer/installed.json", json_encode([
        'packages' => $packages,
    ], JSON_THROW_ON_ERROR));

    $manifest = new PluginPackageManifest($filesystem, $basePath, $manifestPath);
    $manifest->build();

    return $filesystem->getRequire($manifestPath);
}

class PluginPackageManifestTestPlugin extends ServiceProvider {}

class PluginPackageManifestExistingProvider extends ServiceProvider {}
