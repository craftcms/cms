<?php

declare(strict_types=1);

use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Mockery\MockInterface;

test('restores project config event muting when migration fails', function () {
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Mockery::mock(app(ProjectConfig::class))->makePartial();
    $projectConfig->muteEvents = false;
    $projectConfig->shouldReceive('find')->once()->andReturn([
        'fields.test' => ['type' => 'craft\fields\PlainText'],
    ]);
    $projectConfig->shouldReceive('set')
        ->once()
        ->with('fields.test.type', PlainText::class)
        ->andThrow(new RuntimeException('Failed to update project config'));
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/0000_00_00_000004_migrate_field_types.php';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and($projectConfig->muteEvents)->toBeFalse();
});
