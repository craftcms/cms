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
        if (Schema::hasTable(Table::RELEASES)) {
            return;
        }

        Schema::create(Table::RELEASES, function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('plannedAt')->nullable();
            $table->dateTime('publishedAt')->nullable();
            $table->string('status')->default('draft');
            $table->integer('createdById')->nullable();
            $table->integer('scheduledById')->nullable();
            $table->text('lastError')->nullable();
            $table->unsignedSmallInteger('publishAttempts')->default(0);
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
            $table->char('uid', 36)->unique();
        });

        Schema::create(Table::RELEASE_ITEMS, function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('releaseId');
            $table->integer('draftId')->nullable();
            $table->integer('canonicalId');
            $table->integer('publishedElementId')->nullable();
            $table->dateTime('publishedAt')->nullable();
            $table->dateTime('dateCreated');
            $table->dateTime('dateUpdated');
        });

        Schema::createIndex(Table::RELEASES, ['status', 'publishedAt']);
        Schema::createIndex(Table::RELEASE_ITEMS, ['draftId'], unique: true);
        Schema::createIndex(Table::RELEASE_ITEMS, ['releaseId', 'canonicalId'], unique: true);
        Schema::createIndex(Table::RELEASE_ITEMS, ['canonicalId']);

        Schema::table(Table::RELEASES, fn (Blueprint $table) => $table->foreign('createdById')->references('id')->on(Table::USERS)->nullOnDelete());
        Schema::table(Table::RELEASES, fn (Blueprint $table) => $table->foreign('scheduledById')->references('id')->on(Table::USERS)->nullOnDelete());
        Schema::table(Table::RELEASE_ITEMS, fn (Blueprint $table) => $table->foreign('releaseId')->references('id')->on(Table::RELEASES)->cascadeOnDelete());
        Schema::table(Table::RELEASE_ITEMS, fn (Blueprint $table) => $table->foreign('draftId')->references('id')->on(Table::DRAFTS)->nullOnDelete());
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::RELEASE_ITEMS);
        Schema::dropIfExists(Table::RELEASES);
    }
};
