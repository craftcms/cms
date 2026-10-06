<?php

declare(strict_types=1);

use CraftCms\Cms\Mcp\Capabilities\ProjectConfig as ProjectConfigCapability;
use CraftCms\Cms\ProjectConfig\ProjectConfig;

it('reads and writes project config through MCP', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->set('mcpTest', ['value' => 'configured']);

    $capability = app(ProjectConfigCapability::class);
    $written = $capability->write(force: true);
    $current = $capability->get('mcpTest');
    $external = $capability->get('mcpTest', external: true);
    $status = $capability->status('mcpTest')['status'];

    expect($written)->toBe([
        'written' => true,
        'forced' => true,
        'hadFileWriteIssues' => false,
    ])
        ->and($current['value'])->toBe(['value' => 'configured'])
        ->and($external['value'])->toBe(['value' => 'configured'])
        ->and($status)->toMatchArray([
            'path' => 'mcpTest',
            'externalConfigExists' => true,
            'changesPending' => false,
            'hadFileWriteIssues' => false,
            'schemaVersionsCompatible' => true,
        ]);
});
