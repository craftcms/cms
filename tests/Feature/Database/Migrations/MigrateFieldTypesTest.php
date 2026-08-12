<?php

declare(strict_types=1);

use JMac\Testing\Double;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Mockery\MockInterface;

test('restores project config event muting when migration fails', function () {
    /** @var ProjectConfig&MockInterface $projectConfig */
    $projectConfig = Double::for(app(ProjectConfig::class))->passthru();
    $projectConfig->muteEvents = false;
    $projectConfig->expects('find')->returns([
        'fields.test' => ['type' => 'craft\fields\PlainText'],
    ]);
    $projectConfig->expects('set')->with('fields.test.type', PlainText::class)->throws(new RuntimeException('Failed to update project config'));
    app()->instance(ProjectConfig::class, $projectConfig);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/0000_00_00_000004_migrate_field_types.php';

    expect(fn () => $migration->up())->toThrow(RuntimeException::class)
        ->and($projectConfig->muteEvents)->toBeFalse();
});
