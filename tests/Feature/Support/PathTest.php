<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\Facade;

beforeEach(function () {
    $this->sandboxPath = storage_path('framework/testing/path-service/'.uniqid('', true));
    $this->aliases = [
        '@storage' => $this->sandboxPath.'/storage',
        '@tests' => $this->sandboxPath.'/tests',
        '@vendor' => $this->sandboxPath.'/vendor',
        '@templates' => $this->sandboxPath.'/templates',
        '@translations' => $this->sandboxPath.'/translations',
        '@contentMigrations' => $this->sandboxPath.'/content-migrations',
        '@lib' => $this->sandboxPath.'/lib',
    ];

    File::ensureDirectoryExists($this->sandboxPath);

    $this->originalAliases = [];
    foreach ($this->aliases as $alias => $path) {
        $this->originalAliases[$alias] = Aliases::get($alias);
        Aliases::set($alias, $path);
        File::ensureDirectoryExists($path);
    }

    $this->originalProjectConfigFolderName = app(ProjectConfig::class)->folderName;
    app(ProjectConfig::class)->folderName = 'project';

    $this->laravelPath = function (): CraftCms\Cms\Support\Path {
        $laravelPathClass = CraftCms\Cms\Support\Path::class;

        app()->forgetInstance($laravelPathClass);
        Facade::clearResolvedInstance($laravelPathClass);

        return app($laravelPathClass);
    };
});

afterEach(function () {
    app(ProjectConfig::class)->folderName = $this->originalProjectConfigFolderName;

    foreach ($this->originalAliases as $alias => $path) {
        Aliases::set($alias, $path);
    }

    File::deleteDirectory($this->sandboxPath);
    File::deleteDirectory(config_path('craft/__path-test__'));
});

test('base getters resolve expected alias-backed and fixed paths', function () {
    $path = ($this->laravelPath)();

    expect($path->config())->toBe(config_path('craft'))
        ->and($path->tests())->toBe($this->aliases['@tests'])
        ->and($path->vendor())->toBe($this->aliases['@vendor'])
        ->and($path->cpTranslations())->toBe(Aliases::get('@craftcms/resources/translations'))
        ->and($path->siteTranslations())->toBe($this->aliases['@translations'])
        ->and($path->cpTemplates())->toBe(Aliases::get('@craftcms/resources/templates'))
        ->and($path->siteTemplates())->toBe($this->aliases['@templates'])
        ->and($path->licenseKey())->toBe(app(License::class)->keyPath())
        ->and($path->projectConfigFile())->toBe(config_path('craft/project/'.ProjectConfig::CONFIG_FILENAME));
});

test('project config path respects folder name and create flag', function () {
    $path = ($this->laravelPath)();
    $config = app(ProjectConfig::class);
    $config->folderName = '__path-test__';
    $expectedPath = config_path('craft/__path-test__');

    expect($path->projectConfig(create: false))->toBe($expectedPath)
        ->and(File::exists($expectedPath))->toBeFalse();

    expect($path->projectConfig())->toBe($expectedPath)
        ->and(File::isDirectory($expectedPath))->toBeTrue()
        ->and($path->projectConfigFile())->toBe($expectedPath.'/'.ProjectConfig::CONFIG_FILENAME);
});

test('directory getters return the expected path and creation side effects', function (
    string $method,
    string $relativePath,
    bool $writesGitignore,
) {
    $path = ($this->laravelPath)();
    $expectedPath = $this->sandboxPath.'/'.$relativePath;

    File::deleteDirectory($expectedPath);

    expect($path->$method(create: false))->toBe($expectedPath)
        ->and(File::exists($expectedPath))->toBeFalse()
        ->and(File::exists($expectedPath.'/.gitignore'))->toBeFalse();

    expect($path->$method())->toBe($expectedPath)
        ->and(File::isDirectory($expectedPath))->toBeTrue()
        ->and(File::exists($expectedPath.'/.gitignore'))->toBe($writesGitignore);
})->with([
    'storage path' => ['storage', 'storage', false],
    'composer backups path' => ['composerBackups', 'storage/composer-backups', true],
    'config backup path' => ['configBackup', 'storage/config-backups', true],
    'config delta path' => ['configDelta', 'storage/config-deltas', true],
    'runtime path' => ['runtime', 'storage/runtime', true],
    'db backup path' => ['dbBackup', 'storage/backups', false],
    'temp path' => ['temp', 'storage/runtime/temp', false],
    'assets path' => ['assets', 'storage/runtime/assets', false],
    'temp asset uploads path' => ['tempAssetUploads', 'storage/runtime/assets/tempuploads', false],
    'asset sources path' => ['assetSources', 'storage/runtime/assets/sources', false],
    'image editor sources path' => ['imageEditorSources', 'storage/runtime/assets/imageeditor', false],
    'assets icons path' => ['assetsIcons', 'storage/runtime/assets/icons', false],
    'image transforms path' => ['imageTransforms', 'storage/runtime/assets/imagetransforms', false],
    'plugin icons path' => ['pluginIcons', 'storage/runtime/pluginicons', false],
    'log path' => ['logs', 'storage/logs', false],
    'compiled classes path' => ['compiledClasses', 'storage/runtime/compiled_classes', false],
    'compiled templates path' => ['compiledTemplates', 'storage/runtime/compiled_templates', false],
    'session path' => ['sessions', 'storage/runtime/sessions', false],
    'cache path' => ['cache', 'storage/runtime/cache', false],
]);

test('system paths return the expected ordered list', function () {
    $path = ($this->laravelPath)();
    $storagePath = $this->aliases['@storage'];

    expect($path->system())->toBe([
        $this->aliases['@contentMigrations'],
        $this->aliases['@lib'],
        $storagePath.'/composer-backups',
        $storagePath.'/config-backups',
        $storagePath.'/config-deltas',
        config_path('craft'),
        $storagePath.'/backups',
        $storagePath.'/logs',
        $storagePath.'/runtime',
        $this->aliases['@templates'],
        $this->aliases['@translations'],
        $this->aliases['@tests'],
        $this->aliases['@vendor'],
    ]);
});

test('laravel path service and facade return the same values', function () {
    $laravelPath = ($this->laravelPath)();

    expect(Path::projectConfigFile())->toBe($laravelPath->projectConfigFile())
        ->and(Path::temp(create: false))->toBe($laravelPath->temp(create: false))
        ->and(Path::system())->toBe($laravelPath->system());
});

test('laravel path service falls back to application paths when aliases are unavailable', function () {
    Aliases::remove('@storage');
    Aliases::remove('@tests');
    Aliases::remove('@vendor');
    Aliases::remove('@translations');
    Aliases::remove('@contentMigrations');
    Aliases::remove('@lib');

    $path = ($this->laravelPath)();

    expect($path->storage(create: false))->toBe(File::normalizePath(app()->storagePath()))
        ->and($path->tests())->toBe(File::normalizePath(app()->basePath('tests')))
        ->and($path->vendor())->toBe(File::normalizePath(app()->basePath('vendor')))
        ->and($path->siteTranslations())->toBe(File::normalizePath(app()->langPath()))
        ->and($path->system()[0])->toBe(File::normalizePath(app()->basePath('migrations')))
        ->and($path->system()[1])->toBe(File::normalizePath(app()->basePath('yii2-adapter/lib')));
});

test('laravel path service accepts subpaths for representative roots', function () {
    $path = ($this->laravelPath)();

    expect($path->temp('foo.zip', create: false))->toBe($this->aliases['@storage'].'/runtime/temp/foo.zip')
        ->and($path->assetSources('123.jpg', create: false))->toBe($this->aliases['@storage'].'/runtime/assets/sources/123.jpg')
        ->and($path->vendor('composer/InstalledVersions.php'))->toBe($this->aliases['@vendor'].'/composer/InstalledVersions.php')
        ->and($path->projectConfig('foo/bar.yaml', create: false))->toBe(config_path('craft/project/foo/bar.yaml'));
});

test('laravel path service only creates the base directory when a subpath is provided', function () {
    $path = ($this->laravelPath)();

    $tempFilePath = $path->temp('foo.zip');
    $composerBackupPath = $path->composerBackups('composer.json');

    expect($tempFilePath)->toBe($this->aliases['@storage'].'/runtime/temp/foo.zip')
        ->and(File::isDirectory(dirname((string) $tempFilePath)))->toBeTrue()
        ->and(File::exists($tempFilePath))->toBeFalse()
        ->and($composerBackupPath)->toBe($this->aliases['@storage'].'/composer-backups/composer.json')
        ->and(File::isDirectory(dirname((string) $composerBackupPath)))->toBeTrue()
        ->and(File::exists($composerBackupPath))->toBeFalse()
        ->and(File::exists(dirname((string) $composerBackupPath).'/.gitignore'))->toBeTrue();
});

test('ensurePathIsContained', function (bool $expected, string $path) {
    expect(Path::ensurePathIsContained($path))->toBe($expected);
})->with([
    [true, '/'],
    [true, ''],
    [true, 'in/a/path'],
    [false, '../test'],
    [true, './test'],
    [true, 'test'],
    [false, 'foo////../../bar'],
    [true, 'foo/0/0/0/../../bar'],
]);
