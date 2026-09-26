<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\Facade;

beforeEach(function () {
    $this->sandboxPath = File::normalizePath(storage_path('framework/testing/path-service/'.uniqid('', true)));
    $ds = DIRECTORY_SEPARATOR;
    $this->aliases = [
        '@storage' => $this->sandboxPath.$ds.'storage',
        '@tests' => $this->sandboxPath.$ds.'tests',
        '@vendor' => $this->sandboxPath.$ds.'vendor',
        '@templates' => $this->sandboxPath.$ds.'templates',
        '@translations' => $this->sandboxPath.$ds.'translations',
        '@contentMigrations' => $this->sandboxPath.$ds.'content-migrations',
        '@lib' => $this->sandboxPath.$ds.'lib',
    ];

    File::ensureDirectoryExists($this->sandboxPath);

    $this->originalAliases = [];
    foreach ($this->aliases as $alias => $path) {
        $this->originalAliases[$alias] = Aliases::get($alias, false) ?: '';
        Aliases::set($alias, $path);
        File::ensureDirectoryExists($path);
    }

    $this->originalProjectConfigFolderName = app(ProjectConfig::class)->folderName;
    app(ProjectConfig::class)->folderName = 'project';
    $this->originalCompiledTemplatesPath = app(GeneralConfig::class)->compiledTemplatesPath;

    $this->laravelPath = function (): CraftCms\Cms\Support\Path {
        $laravelPathClass = CraftCms\Cms\Support\Path::class;

        app()->forgetInstance($laravelPathClass);
        Facade::clearResolvedInstance($laravelPathClass);

        return app($laravelPathClass);
    };
});

afterEach(function () {
    app(ProjectConfig::class)->folderName = $this->originalProjectConfigFolderName;
    app(GeneralConfig::class)->compiledTemplatesPath = $this->originalCompiledTemplatesPath;

    foreach ($this->originalAliases as $alias => $path) {
        Aliases::set($alias, $path);
    }

    File::deleteDirectory($this->sandboxPath);
    File::deleteDirectory(config_path('craft/__path-test__'));
});

test('base getters resolve expected alias-backed and fixed paths', function () {
    $path = ($this->laravelPath)();

    expect($path->config())->toBe(File::normalizePath(config_path('craft')))
        ->and($path->tests())->toBe($this->aliases['@tests'])
        ->and($path->vendor())->toBe($this->aliases['@vendor'])
        ->and($path->cpTranslations())->toBe(File::normalizePath(Aliases::get('@craftcms/resources/translations')))
        ->and($path->siteTranslations())->toBe($this->aliases['@translations'])
        ->and($path->cpTemplates())->toBe(File::normalizePath(Aliases::get('@craftcms/resources/templates')))
        ->and($path->siteTemplates())->toBe($this->aliases['@templates'])
        ->and($path->licenseKey())->toBe(app(License::class)->keyPath())
        ->and($path->projectConfigFile())->toBe(File::normalizePath(config_path('craft/project/'.ProjectConfig::CONFIG_FILENAME)));
});

test('project config path respects folder name and create flag', function () {
    $path = ($this->laravelPath)();
    $config = app(ProjectConfig::class);
    $config->folderName = '__path-test__';
    $expectedPath = File::normalizePath(config_path('craft/__path-test__'));

    expect($path->projectConfig(create: false))->toBe($expectedPath)
        ->and(File::exists($expectedPath))->toBeFalse();

    expect($path->projectConfig())->toBe($expectedPath)
        ->and(File::isDirectory($expectedPath))->toBeTrue()
        ->and($path->projectConfigFile())->toBe($expectedPath.DIRECTORY_SEPARATOR.ProjectConfig::CONFIG_FILENAME);
});

test('directory getters return the expected path and creation side effects', function (
    string $method,
    string $relativePath,
    bool $writesGitignore,
) {
    $path = ($this->laravelPath)();
    $expectedPath = $this->sandboxPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    File::deleteDirectory($expectedPath);

    expect($path->$method(create: false))->toBe($expectedPath)
        ->and(File::exists($expectedPath))->toBeFalse()
        ->and(File::exists($expectedPath.DIRECTORY_SEPARATOR.'.gitignore'))->toBeFalse();

    expect($path->$method())->toBe($expectedPath)
        ->and(File::isDirectory($expectedPath))->toBeTrue()
        ->and(File::exists($expectedPath.DIRECTORY_SEPARATOR.'.gitignore'))->toBe($writesGitignore);
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

test('compiled templates path can be configured', function () {
    app(GeneralConfig::class)->compiledTemplatesPath = '@storage/custom-compiled-templates';

    $path = ($this->laravelPath)();
    $expectedPath = $this->aliases['@storage'].DIRECTORY_SEPARATOR.'custom-compiled-templates';

    File::deleteDirectory($expectedPath);

    expect($path->compiledTemplates(create: false))->toBe($expectedPath)
        ->and(File::exists($expectedPath))->toBeFalse();

    expect($path->compiledTemplates())->toBe($expectedPath)
        ->and(File::isDirectory($expectedPath))->toBeTrue()
        ->and($path->compiledTemplates('foo.php'))->toBe($expectedPath.DIRECTORY_SEPARATOR.'foo.php');
});

test('system paths return the expected ordered list', function () {
    $path = ($this->laravelPath)();
    $storagePath = $this->aliases['@storage'];
    $ds = DIRECTORY_SEPARATOR;

    expect($path->system())->toBe([
        $this->aliases['@contentMigrations'],
        $this->aliases['@lib'],
        $storagePath.$ds.'composer-backups',
        $storagePath.$ds.'config-backups',
        $storagePath.$ds.'config-deltas',
        File::normalizePath(config_path('craft')),
        $storagePath.$ds.'backups',
        $storagePath.$ds.'logs',
        $storagePath.$ds.'runtime',
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
    $ds = DIRECTORY_SEPARATOR;

    expect($path->temp('foo.zip', create: false))->toBe($this->aliases['@storage'].$ds.'runtime'.$ds.'temp'.$ds.'foo.zip')
        ->and($path->assetSources('123.jpg', create: false))->toBe($this->aliases['@storage'].$ds.'runtime'.$ds.'assets'.$ds.'sources'.$ds.'123.jpg')
        ->and($path->vendor('composer/InstalledVersions.php'))->toBe($this->aliases['@vendor'].$ds.'composer'.$ds.'InstalledVersions.php')
        ->and($path->projectConfig('foo/bar.yaml', create: false))->toBe(File::normalizePath(config_path('craft/project/foo/bar.yaml')));
});

test('laravel path service only creates the base directory when a subpath is provided', function () {
    $path = ($this->laravelPath)();
    $ds = DIRECTORY_SEPARATOR;

    $tempFilePath = $path->temp('foo.zip');
    $composerBackupPath = $path->composerBackups('composer.json');

    expect($tempFilePath)->toBe($this->aliases['@storage'].$ds.'runtime'.$ds.'temp'.$ds.'foo.zip')
        ->and(File::isDirectory(dirname((string) $tempFilePath)))->toBeTrue()
        ->and(File::exists($tempFilePath))->toBeFalse()
        ->and($composerBackupPath)->toBe($this->aliases['@storage'].$ds.'composer-backups'.$ds.'composer.json')
        ->and(File::isDirectory(dirname((string) $composerBackupPath)))->toBeTrue()
        ->and(File::exists($composerBackupPath))->toBeFalse()
        ->and(File::exists(dirname((string) $composerBackupPath).$ds.'.gitignore'))->toBeTrue();
});

test('normalizePath returns a normalized path with a trailing separator for an existing path', function () {
    $ds = DIRECTORY_SEPARATOR;

    expect(Path::normalizePath($this->sandboxPath))->toBe(File::normalizePath($this->sandboxPath).$ds);
});

test('normalizePath returns false for a nonexistent path', function () {
    expect(Path::normalizePath($this->sandboxPath.DIRECTORY_SEPARATOR.'does-not-exist'))->toBeFalse();
});

test('normalizePath returns false for a false input', function () {
    expect(Path::normalizePath(false))->toBeFalse();
});

test('isPathWithinRoots returns true immediately for a path under a temp-dir root', function () {
    $tempRoot = $this->sandboxPath;
    $path = $tempRoot.DIRECTORY_SEPARATOR.'foo.txt';

    expect(Path::isPathWithinRoots($path, [[$tempRoot, true]]))->toBeTrue();
});

test('isPathWithinRoots returns false when the path matches no allowed root', function () {
    $path = $this->sandboxPath.DIRECTORY_SEPARATOR.'foo.txt';

    expect(Path::isPathWithinRoots($path, [[$this->aliases['@storage'], false]]))->toBeFalse();
});

test('isPathWithinRoots returns true for a path under a non-temp root that is not within any system dir', function () {
    $path = $this->sandboxPath.DIRECTORY_SEPARATOR.'not-a-system-dir'.DIRECTORY_SEPARATOR.'foo.txt';
    File::ensureDirectoryExists(dirname($path));

    expect(Path::isPathWithinRoots($path, [[$this->sandboxPath, false]]))->toBeTrue();
});

test('isPathWithinRoots returns false for a path under a non-temp root that is also within a system dir', function () {
    $path = $this->aliases['@tests'].DIRECTORY_SEPARATOR.'foo.txt';

    expect(Path::isPathWithinRoots($path, [[$this->sandboxPath, false]]))->toBeFalse();
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
