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
        if (! Schema::hasTable(Table::AGENT_GUIDANCE)) {
            Schema::create(Table::AGENT_GUIDANCE, function (Blueprint $table) {
                $table->integer('id', true);
                $table->integer('siteId')->nullable();
                $table->integer('userId')->nullable();
                $table->mediumText('body');
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
            });

            Schema::createIndex(Table::AGENT_GUIDANCE, ['siteId', 'userId']);
            Schema::createIndex(Table::AGENT_GUIDANCE, ['userId']);

            Schema::table(Table::AGENT_GUIDANCE, fn (Blueprint $table) => $table->foreign('siteId')->references('id')->on(Table::SITES)->cascadeOnDelete());
            Schema::table(Table::AGENT_GUIDANCE, fn (Blueprint $table) => $table->foreign('userId')->references('id')->on(Table::USERS)->cascadeOnDelete());
        }

        if (! Schema::hasTable(Table::AGENT_SKILLS)) {
            Schema::create(Table::AGENT_SKILLS, function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('title');
                $table->string('description');
                $table->mediumText('body');
                $table->dateTime('dateCreated');
                $table->dateTime('dateUpdated');
                $table->char('uid', 36)->default('0');
            });

            Schema::createIndex(Table::AGENT_SKILLS, ['title'], unique: true);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::AGENT_SKILLS);
        Schema::dropIfExists(Table::AGENT_GUIDANCE);
    }
};
