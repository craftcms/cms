<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Mockery\MockInterface;

test('converts Craft filesystem references and preserves URL semantics', function () {
    foreach (['legacy-public', 'legacy-private', 'manual-public', 'manual-private'] as $disk) {
        config()->set("filesystems.disks.$disk", [
            'driver' => 'local',
            'root' => storage_path("framework/testing/migrations/$disk"),
        ]);
    }
    config()->set('filesystems.disks.manual-public.url', 'https://assets.example.test');

    $filesystems = [
        'legacy-public' => ['settings' => ['hasUrls' => true]],
        'legacy-private' => ['settings' => ['hasUrls' => false]],
    ];
    $volumes = [
        'public' => ['fs' => 'legacy-public'],
        'manual-public' => ['fs' => 'manual-public'],
        'manual-private' => ['fs' => 'manual-private'],
    ];
    $transformers = [
        'craft' => [
            'driver' => 'craft',
            'settings' => [
                'filesystem' => 'legacy-private',
                'subpath' => 'transforms',
            ],
        ],
    ];

    $legacyVolume = Volume::factory()->create(['fs' => 'legacy-public']);
    $manualVolume = Volume::factory()->create(['fs' => 'manual-public']);
    $stored = [];
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->muteEvents = false;
    $projectConfig->shouldReceive('get')->with('fs')->once()->andReturn($filesystems);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_VOLUMES)->once()->andReturn($volumes);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_ASSET_TRANSFORMERS)->once()->andReturn($transformers);
    $projectConfig->shouldReceive('set')->twice()->andReturnUsing(function (string $path, array $value) use (&$stored): bool {
        $stored[$path] = $value;

        return true;
    });
    $projectConfig->shouldReceive('remove')->once()->with('fs', Mockery::type('string'));
    $projectConfig->shouldReceive('saveModifiedConfigData')->once();
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';
    $migration->up();

    expect($stored[ProjectConfig::PATH_VOLUMES])
        ->toMatchArray([
            'public' => ['fs' => 'legacy-public', 'hasUrls' => true],
            'manual-public' => ['fs' => 'manual-public', 'hasUrls' => true],
            'manual-private' => ['fs' => 'manual-private', 'hasUrls' => false],
        ])
        ->and($stored[ProjectConfig::PATH_ASSET_TRANSFORMERS]['craft']['settings'])
        ->toBe([
            'subpath' => 'transforms',
            'disk' => 'legacy-private',
            'hasUrls' => false,
        ])
        ->and($legacyVolume->refresh()->fs)->toBe('legacy-public')
        ->and($legacyVolume->hasUrls)->toBeTrue()
        ->and($manualVolume->refresh()->fs)->toBe('manual-public')
        ->and($manualVolume->hasUrls)->toBeTrue()
        ->and($projectConfig->muteEvents)->toBeFalse();
});

test('fails before mutation when a legacy filesystem has no matching Laravel disk', function () {
    $volume = Volume::factory()->create(['fs' => 'missing-storage']);
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->shouldReceive('get')->with('fs')->once()->andReturn([
        'missing-storage' => ['settings' => ['hasUrls' => true]],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_VOLUMES)->once()->andReturn([
        'volume' => ['fs' => 'missing-storage'],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_ASSET_TRANSFORMERS)->once()->andReturn([]);
    $projectConfig->shouldNotReceive('set');
    $projectConfig->shouldNotReceive('remove');
    $projectConfig->shouldNotReceive('saveModifiedConfigData');
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';

    expect(fn () => $migration->up())
        ->toThrow(RuntimeException::class, 'Configure Laravel filesystem disks named [missing-storage]')
        ->and($volume->refresh()->fs)->toBe('missing-storage');
});

test('suggests an equivalent disk config for a missing Local filesystem', function () {
    Volume::factory()->create(['fs' => 'missing-local']);
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->shouldReceive('get')->with('fs')->once()->andReturn([
        'missing-local' => [
            'type' => 'craft\fs\Local',
            'settings' => [
                'hasUrls' => true,
                'path' => '/var/www/storage/uploads',
                'url' => 'https://cdn.example.test/uploads',
            ],
        ],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_VOLUMES)->once()->andReturn([
        'volume' => ['fs' => 'missing-local'],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_ASSET_TRANSFORMERS)->once()->andReturn([]);
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class, str_replace("\r\n", "\n", <<<'TEXT'
        'missing-local' => [
            'driver' => 'local',
            'root' => '/var/www/storage/uploads',
            'url' => 'https://cdn.example.test/uploads',
        ],
        TEXT));
});

test('falls back to a manual TODO for a missing filesystem of an unrecognized type', function () {
    Volume::factory()->create(['fs' => 'missing-custom']);
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->shouldReceive('get')->with('fs')->once()->andReturn([
        'missing-custom' => [
            'type' => 'Vendor\S3\Fs',
            'settings' => ['bucket' => 'my-bucket'],
        ],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_VOLUMES)->once()->andReturn([
        'volume' => ['fs' => 'missing-custom'],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_ASSET_TRANSFORMERS)->once()->andReturn([]);
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';

    expect(fn () => $migration->up())->toThrow(
        RuntimeException::class,
        'no automatic Laravel disk equivalent for Craft filesystem type "Vendor\S3\Fs"',
    );
});
