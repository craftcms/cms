<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\ProjectConfig;

it('renames approved public MCP tools to their snake_case names', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->set('mcp.publicTools', ['craft-context-get', 'craft-query', 'acme.reviews']);

    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_04_000000_rename_public_mcp_tools.php';
    $migration->up();

    expect($projectConfig->get('mcp.publicTools'))->toBe(['craft_context_get', 'craft_query', 'acme.reviews']);
});
