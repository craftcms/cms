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
        if (! Schema::hasColumn(Table::SECTIONS, 'workflowId')) {
            Schema::table(Table::SECTIONS, fn (Blueprint $table) => $table->integer('workflowId')->nullable());
        }

        if (Schema::hasTable(Table::WORKFLOWS)) {
            return;
        }

        Schema::create(Table::WORKFLOWS, function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->jsonb('stages');
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->unique();
        });

        Schema::create(Table::WORKFLOWRUNS, function (Blueprint $table) {
            $table->id();
            $table->integer('workflowId')->nullable();
            $table->integer('draftId');
            $table->integer('authorId')->nullable();
            $table->unsignedBigInteger('activityRootEventId');
            $table->unsignedSmallInteger('currentStage')->default(0);
            $table->string('currentStageResult')->nullable();
            $table->string('status');
            $table->jsonb('payload')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::createIndex(Table::WORKFLOWRUNS, ['activityRootEventId'], unique: true);
        Schema::createIndex(Table::WORKFLOWRUNS, ['draftId', 'dateCreated']);
        Schema::createIndex(Table::WORKFLOWRUNS, ['draftId', 'status']);

        Schema::table(Table::SECTIONS, fn (Blueprint $table) => $table->foreign('workflowId')->references('id')->on(Table::WORKFLOWS)->nullOnDelete());
        Schema::table(Table::WORKFLOWRUNS, fn (Blueprint $table) => $table->foreign('activityRootEventId')->references('id')->on(Table::ACTIVITYEVENTS)->cascadeOnDelete());
        Schema::table(Table::WORKFLOWRUNS, fn (Blueprint $table) => $table->foreign('workflowId')->references('id')->on(Table::WORKFLOWS)->nullOnDelete());
        Schema::table(Table::WORKFLOWRUNS, fn (Blueprint $table) => $table->foreign('authorId')->references('id')->on(Table::USERS)->nullOnDelete());
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::WORKFLOWRUNS);

        if (Schema::hasColumn(Table::SECTIONS, 'workflowId')) {
            Schema::table(Table::SECTIONS, function (Blueprint $table) {
                $table->dropForeign(['workflowId']);
                $table->dropColumn('workflowId');
            });
        }

        Schema::dropIfExists(Table::WORKFLOWS);
    }
};
