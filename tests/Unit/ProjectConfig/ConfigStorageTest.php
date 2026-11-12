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

it('preserves hidden files and directories when replacing YAML exports', function () {
    File::writeToFile($this->directory.'/project/.gitignore', 'keep');
    File::writeToFile($this->directory.'/project/.hidden/file.txt', 'keep');
    File::writeToFile($this->directory.'/project/obsolete/file.yaml', 'obsolete: true');

    $storage = new ConfigStorage;
    $storage->writeYaml('project', ['system' => ['name' => 'Example']]);

    expect(File::get($this->directory.'/project/.gitignore'))->toBe('keep');
    expect(File::get($this->directory.'/project/.hidden/file.txt'))->toBe('keep');
    expect(File::exists($this->directory.'/project/obsolete'))->toBeFalse();
    expect($storage->readYaml('project'))->toBe(['system' => ['name' => 'Example']]);
});

it('replaces YAML exports in place, and keeps them when a write fails', function () {
    $entryTypeUid = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    $fieldUid = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    $config = [
        'dateModified' => 1,
        'entryTypes' => [$entryTypeUid => ['handle' => 'foo']],
        'fields' => [$fieldUid => ['handle' => 'body']],
    ];
    $base = $this->directory.'/project';
    File::writeToFile("$base/entryTypes/Foo--$entryTypeUid.yaml", "handle: Foo\n");
    File::writeToFile("$base/sections/old--cccccccc-cccc-4ccc-8ccc-cccccccccccc.yaml", "handle: old\n");
    File::writeToFile("$base/globalSets/old--eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee.yaml", "handle: old\n");
    File::writeToFile("$base/globalSets/.hidden/file.txt", 'keep');

    $storage = new ConfigStorage;
    $storage->writeYaml('project', $config);

    $files = collect(File::allFiles($base, true))->map->getRelativePathname()->sort()->values()->all();
    expect($files)->toBe([
        'entryTypes/foo--'.$entryTypeUid.'.yaml',
        'fields/body--'.$fieldUid.'.yaml',
        'globalSets/.hidden/file.txt',
        'project.yaml',
    ])->and(File::exists("$base/sections"))->toBeFalse();

    // A failed write leaves the existing files in place
    $config['fields']['dddddddd-dddd-4ddd-8ddd-dddddddddddd'] = ['handle' => 'summary'];
    chmod("$base/fields", 0555);

    try {
        expect(fn () => $storage->writeYaml('project', $config))->toThrow(Exception::class, 'Unable to write new project config files: ');
    } finally {
        chmod("$base/fields", 0775);
    }

    expect(File::exists("$base/entryTypes/foo--$entryTypeUid.yaml"))->toBeTrue()
        ->and(File::exists("$base/project.yaml"))->toBeTrue()
        ->and(Cache::get(ProjectConfig::FILE_ISSUES_CACHE_KEY))->toBeTrue();
})->skipOnWindows();

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
