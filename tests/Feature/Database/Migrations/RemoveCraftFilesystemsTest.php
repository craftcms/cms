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

    $legacyVolume = Volume::factory()->create(['fs' => 'legacy-public']);
    $manualVolume = Volume::factory()->create(['fs' => 'manual-public']);
    $stored = [];
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->muteEvents = false;
    $projectConfig->shouldReceive('get')->with('fs')->once()->andReturn($filesystems);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_VOLUMES)->once()->andReturn($volumes);
    $projectConfig->shouldReceive('set')->once()->andReturnUsing(function (string $path, array $value) use (&$stored): bool {
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
    $projectConfig->shouldNotReceive('set');
    $projectConfig->shouldNotReceive('remove');
    $projectConfig->shouldNotReceive('saveModifiedConfigData');
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';

    expect(fn () => $migration->up())
        ->toThrow(fn (RuntimeException $exception) => expect(str_replace("\r\n", "\n", $exception->getMessage()))
            ->toContain('[missing-storage]')
            ->toContain('config/filesystems.php')
            ->toContain("'missing-storage' => ["))
        ->and($volume->refresh()->fs)->toBe('missing-storage');
});

test('suggests disk config for an adapter-generated Craft 5 filesystem disk', function () {
    config()->set('filesystems.disks.siteAssets', [
        'driver' => 'local',
        'root' => '/runtime/generated/root',
        '_craft' => true,
    ]);

    Volume::factory()->create(['fs' => 'siteAssets']);
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->shouldReceive('get')->with('fs')->once()->andReturn([
        'siteAssets' => [
            'type' => 'craft\\fs\\Local',
            'hasUrls' => true,
            'url' => '@assetBaseUrl/site',
            'settings' => [
                'path' => '@assetBasePath/site',
            ],
        ],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_VOLUMES)->once()->andReturn([
        '0193dc64-5499-4e28-95dd-f8f603154851' => ['fs' => 'siteAssets'],
    ]);
    $projectConfig->shouldNotReceive('set');
    $projectConfig->shouldNotReceive('remove');
    $projectConfig->shouldNotReceive('saveModifiedConfigData');
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';

    $expectedMessage = str_replace(["\r\n", "\r"], "\n", <<<'MESSAGE'
            The Craft Filesystem concept has been removed.

            Configure Laravel filesystem disks named [siteAssets] in config/filesystems.php with settings equivalent to the previous Craft Filesystem definitions.

            If config/filesystems.php does not exist, publish it first:

                php artisan config:publish filesystems --no-interaction

            Add these entries to its `disks` array:

                    'siteAssets' => [
                        'driver' => 'local',
                        'root' => '@assetBasePath/site',
                        'url' => '@assetBaseUrl/site',
                    ],

            Then run the upgrade again.
            MESSAGE);

    expect(fn () => $migration->up())->toThrow(fn (RuntimeException $exception) => expect(str_replace(["\r\n", "\r"], "\n", $exception->getMessage()))
        ->toBe($expectedMessage));
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
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';

    expect(fn () => $migration->up())->toThrow(fn (RuntimeException $exception) => expect(str_replace("\r\n", "\n", $exception->getMessage()))
        ->toContain("'missing-local' => [")
        ->toContain("'driver' => 'local',")
        ->toContain("'root' => '/var/www/storage/uploads',")
        ->toContain("'url' => 'https://cdn.example.test/uploads',"));
});

test('suggests equivalent disk configs for plugin filesystems without exposing credentials', function () {
    Volume::factory()->create(['fs' => 'azureAssets']);
    Volume::factory()->create(['fs' => 's3Assets']);
    Volume::factory()->create(['fs' => 'gcsAssets']);
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->shouldReceive('get')->with('fs')->once()->andReturn([
        'azureAssets' => [
            'type' => 'craft\azureblob\Fs',
            'settings' => [
                'connectionString' => 'AccountName=craft;AccountKey=inline-azure-secret',
                'container' => '$AZURE_CONTAINER',
                'subfolder' => 'media',
                'hasUrls' => true,
                'url' => 'https://cdn.example.test/',
            ],
        ],
        's3Assets' => [
            'type' => 'craft\awss3\Fs',
            'settings' => [
                'keyId' => '$S3_KEY_ID',
                'secret' => 'inline-s3-secret',
                'region' => 'eu-west-1',
                'bucket' => 'craft-bucket',
                'makeUploadsPublic' => false,
            ],
        ],
        'gcsAssets' => [
            'type' => 'craft\googlecloud\Fs',
            'settings' => [
                'projectId' => 'craft-project',
                'keyFileContents' => '{"private_key":"inline-gcs-secret"}',
                'bucket' => 'craft-gcs',
                'subfolder' => 'uploads',
                'hasUrls' => true,
                'url' => 'https://gcs-cdn.example.test/',
            ],
        ],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_VOLUMES)->once()->andReturn([
        'azure' => ['fs' => 'azureAssets'],
        's3' => ['fs' => 's3Assets'],
        'gcs' => ['fs' => 'gcsAssets'],
    ]);
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';

    expect(fn () => $migration->up())->toThrow(fn (RuntimeException $exception) => expect(str_replace("\r\n", "\n", $exception->getMessage()))
        ->toContain(str_replace("\r\n", "\n", <<<'PHP'
                    'azureAssets' => [
                        // Set AZURE_ASSETS_CONNECTION_STRING in .env to the previous filesystem's value.
                        // Install with: composer require azure-oss/storage-blob-laravel
                        'driver' => 'azure-storage-blob',
                        'connection_string' => env('AZURE_ASSETS_CONNECTION_STRING'),
                        'container' => env('AZURE_CONTAINER'),
                        'prefix' => 'media',
                        'url' => 'https://cdn.example.test',
                    ],
            PHP))
        ->toContain(str_replace("\r\n", "\n", <<<'PHP'
                    's3Assets' => [
                        // Set S3_ASSETS_SECRET in .env to the previous filesystem's value.
                        'driver' => 's3',
                        'key' => env('S3_KEY_ID'),
                        'secret' => env('S3_ASSETS_SECRET'),
                        'region' => 'eu-west-1',
                        'bucket' => 'craft-bucket',
                        'visibility' => 'private',
                    ],
            PHP))
        ->toContain(str_replace("\r\n", "\n", <<<'PHP'
                    'gcsAssets' => [
                        // Set GCS_ASSETS_KEY_FILE in .env to the previous filesystem's value.
                        // Install with: composer require spatie/laravel-google-cloud-storage:^2.4.2
                        'driver' => 'gcs',
                        'project_id' => 'craft-project',
                        'key_file' => json_decode((string) env('GCS_ASSETS_KEY_FILE'), true),
                        'bucket' => 'craft-gcs',
                        'path_prefix' => 'uploads',
                        'storage_api_uri' => 'https://gcs-cdn.example.test',
                        'visibility' => 'public',
                    ],
            PHP))
        ->not->toContain('inline-azure-secret')
        ->not->toContain('inline-gcs-secret')
        ->not->toContain('inline-s3-secret'));
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
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';

    expect(fn () => $migration->up())->toThrow(fn (RuntimeException $exception) => expect($exception->getMessage())
        ->toContain('"Vendor\S3\Fs"')
        ->toContain('create this manually'));
});

test('doesn’t require a disk or asset_transformer for a Craft 5 volume’s transform filesystem', function () {
    config()->set('filesystems.disks.volume-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/migrations/volume-source'),
    ]);
    config()->offsetUnset('filesystems.disks.volume-transforms');
    Volume::factory()->create(['fs' => 'volume-source']);
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->muteEvents = false;
    $projectConfig->shouldReceive('get')->with('fs')->once()->andReturn([
        'volume-source' => ['settings' => ['hasUrls' => true]],
        'volume-transforms' => ['settings' => ['hasUrls' => true]],
    ]);
    $projectConfig->shouldReceive('get')->with(ProjectConfig::PATH_VOLUMES)->once()->andReturn([
        'volume' => [
            'name' => 'Images',
            'fs' => 'volume-source',
            'transformFs' => 'volume-transforms',
            'transformSubpath' => 'transforms',
        ],
    ]);
    $projectConfig->shouldReceive('set')->once()->with(
        ProjectConfig::PATH_VOLUMES,
        Mockery::on(fn (array $volumes): bool => $volumes['volume']['fs'] === 'volume-source'
            && $volumes['volume']['transformFs'] === 'volume-transforms'
            && $volumes['volume']['transformSubpath'] === 'transforms'),
        Mockery::type('string'),
    );
    $projectConfig->shouldReceive('remove')->once()->with('fs', Mockery::type('string'));
    $projectConfig->shouldReceive('saveModifiedConfigData')->once();
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_09_01_000000_remove_craft_filesystems.php';
    $migration->up();
});
