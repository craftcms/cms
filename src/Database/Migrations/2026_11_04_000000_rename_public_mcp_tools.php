<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // MCP tool names are snake_case now, so update the public server’s approvals of Craft’s public tools.
        $renamed = [
            'craft-context-get' => 'craft_context_get',
            'craft-query' => 'craft_query',
        ];

        $projectConfig = app(ProjectConfig::class);
        $tools = $projectConfig->get('mcp.publicTools') ?? [];
        $newTools = array_map(fn (string $tool): string => $renamed[$tool] ?? $tool, $tools);

        if ($newTools !== $tools) {
            $projectConfig->set('mcp.publicTools', $newTools, 'Rename public MCP tools');
            $projectConfig->saveModifiedConfigData();
        }
    }

    public function down(): void {}
};
