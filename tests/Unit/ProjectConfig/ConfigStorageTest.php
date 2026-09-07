<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\ConfigStorage;
use CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->directory = storage_path('framework/testing/project-config/'.uniqid('', true));
    Path::shouldReceive('config')->with('project')->andReturn($this->directory.'/project');
    Path::shouldReceive('configDelta')->with('delta.yaml')->andReturn($this->directory.'/deltas/delta.yaml');
});

afterEach(function () {
    File::deleteDirectory($this->directory);
});

it('returns empty config before installation', function () {
    Context::addHidden('craft.isInstalled', false);

    expect(new ConfigStorage()->readDatabase(null))->toBe([]);
});

it('strips filename handles only when followed by a UUID', function (string $filename, string $key) {
    File::writeToFile($this->directory.'/project/project.yaml', '{}');
    File::writeToFile($this->directory.'/project/fields/'.$filename.'.yaml', 'name: Example');

    expect(new ConfigStorage()->readYaml('project'))->toBe(['fields' => [$key => ['name' => 'Example']]]);
})->with([
    'lowercase UUID' => ['example--d62a289c-4a1a-4e4b-955e-3cf9973454e4', 'd62a289c-4a1a-4e4b-955e-3cf9973454e4'],
    'uppercase UUID' => ['example--D62A289C-4A1A-4E4B-955E-3CF9973454E4', 'D62A289C-4A1A-4E4B-955E-3CF9973454E4'],
    'invalid UUID' => ['example--0123456789abcdef0123456789abcdef0123', 'example--0123456789abcdef0123456789abcdef0123'],
]);

it('annotates YAML with the first UUID name on each line', function (string $uid) {
    $otherUid = '6fa459ea-ee8a-4ca4-894e-db77e160355e';
    $storage = new ConfigStorage;
    $storage->writeYaml('project', [
        'example' => "$uid $otherUid",
        'meta' => ['__names__' => [$uid => 'First component', $otherUid => 'Second component']],
    ]);

    expect(File::get($this->directory.'/project/project.yaml'))->toContain("example: '$uid $otherUid' # First component");
})->with(['d62a289c-4a1a-4e4b-955e-3cf9973454e4', 'D62A289C-4A1A-4E4B-955E-3CF9973454E4']);

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
