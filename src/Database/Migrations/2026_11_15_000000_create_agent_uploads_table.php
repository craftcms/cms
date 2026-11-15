<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Migration;
use CraftCms\Cms\Database\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable(Table::AGENT_UPLOADS)) {
            return;
        }

        Schema::create(Table::AGENT_UPLOADS, function (Blueprint $table) {
            $table->integer('id', true);
            $table->char('conversationId', 36);
            $table->integer('assetId');
            $table->integer('userId');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->default('0');
        });

        Schema::createIndex(Table::AGENT_UPLOADS, ['assetId'], unique: true);
        Schema::createIndex(Table::AGENT_UPLOADS, ['conversationId']);
        Schema::createIndex(Table::AGENT_UPLOADS, ['userId']);

        Schema::table(Table::AGENT_UPLOADS, fn (Blueprint $table) => $table->foreign('assetId')->references('id')->on(Table::ASSETS)->cascadeOnDelete());
        Schema::table(Table::AGENT_UPLOADS, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::AGENT_UPLOADS);
    }
};
