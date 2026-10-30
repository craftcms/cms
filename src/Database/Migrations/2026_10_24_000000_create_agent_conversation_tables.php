<?php

declare(strict_types=1);

use CraftCms\Cms\Database\LaravelMigrations;
use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(LaravelMigrations::class)->ensureAgentConversationTables();
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::AGENT_CONVERSATION_MESSAGES);
        Schema::dropIfExists(Table::AGENT_CONVERSATIONS);
    }
};
