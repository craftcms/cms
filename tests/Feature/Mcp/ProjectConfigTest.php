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

it('redacts credentials, including environment variable references', function () {
    app(ProjectConfig::class)->set('plugins.mcpTest', [
        'licenseKey' => 'ABCD-1234',
        'settings' => ['apiKey' => 'sk_live_123', 'secretToken' => '$MCP_TEST_TOKEN', 'region' => 'us-east-1'],
    ]);

    $capability = app(ProjectConfigCapability::class);

    expect($capability->get('plugins.mcpTest')['value'])->toEqualCanonicalizing([
        'licenseKey' => '•••••••••',
        'settings' => ['apiKey' => '•••••••••••', 'secretToken' => '•••••••••••••••', 'region' => 'us-east-1'],
    ])
        ->and($capability->get('plugins.mcpTest.settings.apiKey')['value'])->toBe('•••••••••••')
        ->and(json_encode($capability->status()['status']['appliedChanges'], JSON_UNESCAPED_UNICODE))
        ->not->toContain('sk_live_123')
        ->not->toContain('ABCD-1234')
        ->not->toContain('MCP_TEST_TOKEN');
});
