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
        if (Schema::hasTable(Table::ACTIVITYEVENTS)) {
            return;
        }

        Schema::create(Table::ACTIVITYEVENTS, function (Blueprint $table) {
            $table->id();
            $table->string('eventType');
            $table->string('source');
            $table->string('actorType');
            $table->unsignedBigInteger('actorId')->nullable();
            $table->string('subjectType')->nullable();
            $table->string('subjectId')->nullable();
            $table->unsignedBigInteger('siteId')->nullable();
            $table->jsonb('payload');
            $table->dateTime('occurredAt');
        });

        Schema::createIndex(Table::ACTIVITYEVENTS, ['subjectType', 'subjectId', 'siteId', 'occurredAt', 'id']);
        Schema::createIndex(Table::ACTIVITYEVENTS, ['occurredAt', 'id']);
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::ACTIVITYEVENTS);
    }
};
