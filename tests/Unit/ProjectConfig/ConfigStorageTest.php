<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\ConfigStorage;
use CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->directory = storage_path('framework/testing/project-config/'.uniqid('', true));
    Path::shouldReceive('config')->with('project')->andReturn($this->directory.'/project');
    Path::shouldReceive('configDelta')->with('delta.yaml')->andReturn($this->directory.'/deltas/delta.yaml');
});

afterEach(function () {
    File::deleteDirectory($this->directory);
});

it('decodes legacy config before parsing it', function (string $format, bool $base64) {
    $config = ['plugin' => ['name' => 'Example']];
    $value = match ($format) {
        'json' => '{"plugin":{"name":"Example"}}',
        'serialized' => serialize($config),
        'encrypted' => 'crypt:'.Crypt::encryptString('{"plugin":{"name":"Example"}}'),
    };
    $info = new Info;
    $info->setRawAttributes([
        'schemaVersion' => '3.3.0',
        'config' => $base64 ? 'base64:'.base64_encode($value) : $value,
    ]);
    Context::addHidden('craft.info', $info);
    Context::addHidden('craft.isInstalled', true);

    expect(new ConfigStorage()->readDatabase(null))->toBe($config);
})->with(['json', 'serialized', 'encrypted'])->with([false, true]);

it('loads empty legacy config', function (?string $value) {
    $info = new Info;
    $info->setRawAttributes(['schemaVersion' => '3.3.0', 'config' => $value]);
    Context::addHidden('craft.info', $info);
    Context::addHidden('craft.isInstalled', true);

    expect(new ConfigStorage()->readDatabase(null))->toBe([]);
})->with([null, '']);

it('preserves exported files when a yaml written listener fails', function () {
    $storage = new ConfigStorage;
    $config = ['system' => ['name' => 'Example']];
    Event::listen(YamlFilesWritten::class, function () {
        throw new RuntimeException('Listener failed');
    });

    expect(fn () => $storage->writeYaml('project', $config))->toThrow(RuntimeException::class, 'Listener failed');

    expect($storage->readYaml('project'))->toBe($config);
    expect(Cache::has(ProjectConfig::FILE_ISSUES_CACHE_KEY))->toBeFalse();
});

it('uses configured Unix permissions for export and delta directories', function () {
    $mode = Cms::config()->defaultDirMode;
    $mask = umask(0);
    Cms::config()->defaultDirMode = 0700;
    $storage = new ConfigStorage;

    try {
        $storage->writeYaml('project', [
            'fields' => ['d62a289c-4a1a-4e4b-955e-3cf9973454e4' => ['name' => 'Example']],
        ]);
        $storage->writeDelta([['added' => ['test' => true]]], 1);

        expect(fileperms($this->directory.'/project') & 0777)->toBe(0700);
        expect(fileperms($this->directory.'/project/fields') & 0777)->toBe(0700);
        expect(fileperms($this->directory.'/deltas') & 0777)->toBe(0700);
    } finally {
        Cms::config()->defaultDirMode = $mode;
        umask($mask);
    }
})->skipOnWindows();
