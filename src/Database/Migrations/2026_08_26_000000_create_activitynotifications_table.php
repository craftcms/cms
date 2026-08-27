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
        if (Schema::hasTable(Table::ACTIVITYNOTIFICATIONS)) {
            return;
        }

        Schema::create(Table::ACTIVITYNOTIFICATIONS, function (Blueprint $table) {
            $table->unsignedBigInteger('activityEventId');
            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('versionEventId');
        });

        Schema::createIndex(Table::ACTIVITYNOTIFICATIONS, ['activityEventId', 'userId'], unique: true);

        Schema::table(Table::ACTIVITYNOTIFICATIONS, fn (Blueprint $table) => $table->foreign('activityEventId')
            ->references('id')
            ->on(Table::ACTIVITYEVENTS)
            ->cascadeOnDelete());
        Schema::table(Table::ACTIVITYNOTIFICATIONS, fn (Blueprint $table) => $table->foreign('versionEventId')
            ->references('id')
            ->on(Table::ACTIVITYEVENTS)
            ->cascadeOnDelete());
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::ACTIVITYNOTIFICATIONS);
    }
};
