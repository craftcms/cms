<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m261104_000000_snake_case_mcp_tool_names migration.
 */
class m261104_000000_snake_case_mcp_tool_names extends Migration
{
    private const RENAMED_PUBLIC_TOOLS = [
        'craft-context-get' => 'craft_context_get',
        'craft-query' => 'craft_query',
    ];

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $tools = $projectConfig->get('mcp.publicTools') ?? [];
        $renamed = array_map(fn(string $tool) => self::RENAMED_PUBLIC_TOOLS[$tool] ?? $tool, $tools);

        if ($renamed !== $tools) {
            $projectConfig->set('mcp.publicTools', $renamed, 'Rename public MCP tools');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m261104_000000_snake_case_mcp_tool_names cannot be reverted.\n";
        return false;
    }
}
