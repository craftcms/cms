<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\Facades\Schema;

it('upgrades legacy image transform configuration to nested operations', function () {
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_08_14_000000_named_image_transform_drivers.php';

    $projectConfig = app(ProjectConfig::class);
    $muteEvents = $projectConfig->muteEvents;
    $projectConfig->muteEvents = true;
    $projectConfig->set('imageTransforms.legacy-transform', [
        'name' => 'Legacy',
        'handle' => 'legacy',
        'width' => 640,
        'height' => null,
        'mode' => 'fit',
        'position' => 'center-center',
        'quality' => null,
        'format' => null,
        'interlace' => 'none',
        'fill' => null,
        'upscale' => true,
        'blur' => 4,
    ]);
    $projectConfig->flush();
    $projectConfig->reset();
    $projectConfig->muteEvents = $muteEvents;

    $migration->up();

    expect(Schema::hasColumns(Table::IMAGETRANSFORMS, ['driver', 'operations']))->toBeTrue()
        ->and($projectConfig->get('imageTransforms.legacy-transform'))->toEqual([
            'name' => 'Legacy',
            'handle' => 'legacy',
            'driver' => null,
            'operations' => [
                'fill' => null,
                'format' => null,
                'height' => null,
                'interlace' => 'none',
                'mode' => 'fit',
                'position' => 'center-center',
                'quality' => null,
                'upscale' => true,
                'width' => 640,
                'blur' => 4,
            ],
        ]);
});
