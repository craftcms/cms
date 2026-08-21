<?php

declare(strict_types=1);

use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\License\License;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\Facade;

beforeEach(function () {
    $this->sandboxPath = File::normalizePath(storage_path('framework/testing/path-service/'.uniqid('', true)));
    $this->originalStoragePath = app()->storagePath();
    $this->originalLangPath = app()->langPath();
    $this->originalViewPaths = config('view.paths');
    $this->originalProjectConfigFolderName = app(ProjectConfig::class)->folderName;
    $this->originalCompiledTemplatesPath = app(GeneralConfig::class)->compiledTemplatesPath;

    app()->useStoragePath($this->sandboxPath.'/storage');
    app()->useLangPath($this->sandboxPath.'/translations');
    config(['view.paths' => [$this->sandboxPath.'/templates']]);

    foreach (['storage', 'translations', 'templates'] as $directory) {
        File::ensureDirectoryExists($this->sandboxPath."/$directory");
    }

    app(ProjectConfig::class)->folderName = 'project';

    $this->path = function (): CraftCms\Cms\Support\Path {
        app()->forgetInstance(CraftCms\Cms\Support\Path::class);
        Facade::clearResolvedInstance(CraftCms\Cms\Support\Path::class);

        return app(CraftCms\Cms\Support\Path::class);
    };
});

afterEach(function () {
    app()->useStoragePath($this->originalStoragePath);
    app()->useLangPath($this->originalLangPath);
    config(['view.paths' => $this->originalViewPaths]);
    app(ProjectConfig::class)->folderName = $this->originalProjectConfigFolderName;
    app(GeneralConfig::class)->compiledTemplatesPath = $this->originalCompiledTemplatesPath;

    File::deleteDirectory($this->sandboxPath);
    File::deleteDirectory(config_path('craft/__path-test__'));
});

test('base getters use Laravel and package paths', function () {
    $path = ($this->path)();
    $packagePath = dirname(__DIR__, 3);

    expect($path->config())->toBe(File::normalizePath(config_path('craft')))
        ->and($path->storage(create: false))->toBe(File::normalizePath(app()->storagePath()))
        ->and($path->tests())->toBe(File::normalizePath(base_path('tests')))
        ->and($path->vendor())->toBe(File::normalizePath(base_path('vendor')))
        ->and($path->package())->toBe(File::normalizePath($packagePath))
        ->and($path->resources())->toBe(File::normalizePath($packagePath.'/resources'))
        ->and($path->cpTranslations())->toBe(File::normalizePath($packagePath.'/resources/translations'))
        ->and($path->siteTranslations())->toBe(File::normalizePath(app()->langPath()))
        ->and($path->cpTemplates())->toBe(File::normalizePath($packagePath.'/resources/templates'))
        ->and($path->siteTemplates())->toBe(File::normalizePath($this->sandboxPath.'/templates'))
        ->and($path->licenseKey())->toBe(app(License::class)->keyPath())
        ->and($path->projectConfigFile())->toBe(File::normalizePath(config_path('craft/project/'.ProjectConfig::CONFIG_FILENAME)));
});

test('project config path respects folder name and create flag', function () {
    $path = ($this->path)();
    app(ProjectConfig::class)->folderName = '__path-test__';
    $expectedPath = File::normalizePath(config_path('craft/__path-test__'));

    expect($path->projectConfig(create: false))->toBe($expectedPath)
        ->and(File::exists($expectedPath))->toBeFalse()
        ->and($path->projectConfig())->toBe($expectedPath)
        ->and(File::isDirectory($expectedPath))->toBeTrue();
});

test('directory getters use the Laravel storage path', function (string $method, string $relativePath, bool $writesGitignore) {
    $path = ($this->path)();
    $expectedPath = File::normalizePath($this->sandboxPath."/$relativePath");

    File::deleteDirectory($expectedPath);

    expect($path->$method(create: false))->toBe($expectedPath)
        ->and(File::exists($expectedPath))->toBeFalse()
        ->and($path->$method())->toBe($expectedPath)
        ->and(File::isDirectory($expectedPath))->toBeTrue()
        ->and(File::exists($expectedPath.'/.gitignore'))->toBe($writesGitignore);
})->with([
    'storage' => ['storage', 'storage', false],
    'composer backups' => ['composerBackups', 'storage/composer-backups', true],
    'runtime' => ['runtime', 'storage/runtime', true],
    'temp' => ['temp', 'storage/runtime/temp', false],
    'asset sources' => ['assetSources', 'storage/runtime/assets/sources', false],
    'compiled templates' => ['compiledTemplates', 'storage/runtime/compiled_templates', false],
]);

test('compiled templates path accepts an absolute path', function () {
    $expectedPath = $this->sandboxPath.'/custom-compiled-templates';
    app(GeneralConfig::class)->compiledTemplatesPath = $expectedPath;

    $path = ($this->path)();

    expect($path->compiledTemplates(create: false))->toBe(File::normalizePath($expectedPath))
        ->and(File::exists($expectedPath))->toBeFalse()
        ->and($path->compiledTemplates())->toBe(File::normalizePath($expectedPath))
        ->and(File::isDirectory($expectedPath))->toBeTrue();
});

test('compiled templates path rejects aliases', function () {
    app(GeneralConfig::class)->compiledTemplatesPath = '@storage/compiled-templates';

    ($this->path)()->compiledTemplates();
})->throws(InvalidArgumentException::class, 'Path aliases require craftcms/yii2-adapter.');

test('system paths return native application paths', function () {
    $path = ($this->path)();
    $storage = File::normalizePath(app()->storagePath());

    expect($path->system())->toBe([
        base_path('migrations'),
        File::normalizePath("$storage/composer-backups"),
        File::normalizePath("$storage/config-backups"),
        File::normalizePath("$storage/config-deltas"),
        File::normalizePath(config_path('craft')),
        File::normalizePath("$storage/backups"),
        File::normalizePath("$storage/logs"),
        File::normalizePath("$storage/runtime"),
        File::normalizePath($this->sandboxPath.'/templates'),
        File::normalizePath(app()->langPath()),
        File::normalizePath(base_path('tests')),
        File::normalizePath(base_path('vendor')),
    ]);
});

test('path service and facade return the same values', function () {
    $path = ($this->path)();

    expect(Path::projectConfigFile())->toBe($path->projectConfigFile())
        ->and(Path::package('LICENSE.md'))->toBe($path->package('LICENSE.md'))
        ->and(Path::temp(create: false))->toBe($path->temp(create: false))
        ->and(Path::system())->toBe($path->system());
});

test('ensurePathIsContained', function (bool $expected, string $path) {
    expect(Path::ensurePathIsContained($path))->toBe($expected);
})->with([
    [true, '/'],
    [true, ''],
    [true, 'in/a/path'],
    [false, '../test'],
    [true, './test'],
    [false, 'foo////../../bar'],
]);
